<?php
/**
 * Listings – images and files of entries (field types image, gallery, file).
 *
 * Uploads are stored under media/listings/<section_id>/ with random names;
 * the folder is closed to direct access (.htaccess), everything goes through
 * file.php, which checks status, expiry, field visibility and page rights.
 * Images are decoded and encoded again with GD: that drops EXIF data and
 * anything hidden in the file, and scales them to the field's maximum size.
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

namespace Listings;

defined('WB_PATH') or die('This file cannot be accessed directly!');

final class Files
{
    private const TABLE = '{TP}mod_listings_files';

    public const TYPES = ['image', 'gallery', 'file'];

    /** What finfo has to report for an image, and the extension it is stored with. */
    private const IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    /** Never accepted in a file field, whatever the field allows: they could run or render as a page. */
    private const BLOCKED = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps', 'pht', 'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp',
        'htm', 'html', 'xhtml', 'shtml', 'svg', 'svgz', 'xml', 'js', 'mjs', 'css', 'htaccess', 'htpasswd', 'ini', 'exe', 'com', 'bat', 'cmd', 'msi', 'dll', 'jar',
    ];

    /** Content types of common downloads, for servers without fileinfo. */
    private const MIME_BY_EXT = [
        'pdf' => 'application/pdf', 'zip' => 'application/zip', 'txt' => 'text/plain', 'csv' => 'text/csv',
        'odt' => 'application/vnd.oasis.opendocument.text', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation', 'doc' => 'application/msword', 'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
        'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4', 'gpx' => 'application/gpx+xml', 'epub' => 'application/epub+zip',
    ];

    public const DEFAULT_EXTENSIONS ='pdf, zip, txt, odt, ods, odp, doc, docx, xls, xlsx, ppt, pptx, jpg, png';

    /** Defaults of the field options. */
    public const DEFAULT_MAX_FILES = ['image' => 1, 'gallery' => 10, 'file' => 5];
    public const DEFAULT_MAX_MB = ['image' => 8, 'gallery' => 8, 'file' => 10];
    public const DEFAULT_MAX_PX = 1600;
    public const MAX_FILES_LIMIT = 50;
    public const MAX_PX_LIMIT = 4000;

    /** Edge of the thumbnails in lists and forms. */
    private const THUMB_PX = 400;

    /** Larger images are refused before GD would need hundreds of MB for them. */
    private const MAX_PIXELS = 40_000_000;

    /** How long owners and admins may see files of entries that are not public, after a form showed them. */
    private const GRANT_TTL = 7200;

    public static function isUpload(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public static function isImage(string $type): bool
    {
        return $type === 'image' || $type === 'gallery';
    }

    /** Images need GD with JPEG and PNG support. */
    public static function imagesAvailable(): bool
    {
        return function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng') && function_exists('imagecopyresampled');
    }

    public static function maxFiles(array $field): int
    {
        if ($field['type'] === 'image') {
            return 1;
        }
        $max = (int) ($field['options']['max_files'] ?? 0);
        return $max > 0 ? min($max, self::MAX_FILES_LIMIT) : self::DEFAULT_MAX_FILES[$field['type']] ?? 1;
    }

    /** The field's limit, but never more than PHP accepts for one upload. */
    public static function maxBytes(array $field): int
    {
        $mb = (float) ($field['options']['max_mb'] ?? 0);
        $bytes = (int) (($mb > 0 ? $mb : self::DEFAULT_MAX_MB[$field['type']] ?? 5) * 1048576);
        $ini = self::iniBytes((string) ini_get('upload_max_filesize'));
        return $ini > 0 ? min($bytes, $ini) : $bytes;
    }

    /** The whole request may not be larger than this, or PHP drops all fields. */
    public static function maxPostBytes(): int
    {
        return self::iniBytes((string) ini_get('post_max_size'));
    }

    public static function maxPx(array $field): int
    {
        $px = (int) ($field['options']['max_px'] ?? 0);
        return $px > 0 ? min($px, self::MAX_PX_LIMIT) : self::DEFAULT_MAX_PX;
    }

    /** @return list<string> allowed extensions of a file field, lower case without dot */
    public static function extensions(array $field): array
    {
        if (self::isImage($field['type'])) {
            return function_exists('imagecreatefromwebp') ? ['jpg', 'jpeg', 'png', 'gif', 'webp'] : ['jpg', 'jpeg', 'png', 'gif'];
        }
        return self::parseExtensions((string) ($field['options']['extensions'] ?? self::DEFAULT_EXTENSIONS));
    }

    /** @return list<string> */
    public static function parseExtensions(string $text): array
    {
        $list = [];
        foreach (preg_split('/[\s,;]+/', strtolower($text)) ?: [] as $ext) {
            $ext = ltrim(trim($ext), '.');
            if ($ext !== '' && preg_match('/^[a-z0-9]{1,10}$/', $ext) === 1 && !in_array($ext, self::BLOCKED, true)) {
                $list[$ext] = $ext;
            }
        }
        return array_values($list);
    }

    /** The accept attribute of the file input. */
    public static function accept(array $field): string
    {
        return implode(',', array_map(static fn ($e) => '.' . $e, self::extensions($field)));
    }

    /**
     * Adds the limits the form shows and the browser checks (upload) to image and file fields.
     *
     * @param list<array<string, mixed>> $fields
     * @return list<array<string, mixed>>
     */
    public static function withLimits(array $fields): array
    {
        foreach ($fields as &$field) {
            if (self::isUpload($field['type'])) {
                $field['upload'] = [
                    'accept'     => self::accept($field),
                    'max_bytes'  => self::maxBytes($field),
                    'max_text'   => self::formatSize(self::maxBytes($field)),
                    'max_files'  => self::maxFiles($field),
                    'extensions' => implode(', ', array_diff(self::extensions($field), ['jpeg'])),
                ];
            }
        }
        unset($field);
        return $fields;
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }
        return max(1, (int) round($bytes / 1024)) . ' KB';
    }

    /**
     * The files of one entry by field name, in their order.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function forItem(int $sectionId, int $itemId): array
    {
        return self::forItems($sectionId, [$itemId])[$itemId] ?? [];
    }

    /**
     * @param list<int> $itemIds
     * @return array<int, array<string, list<array<string, mixed>>>>
     */
    public static function forItems(int $sectionId, array $itemIds): array
    {
        global $database;
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        $result = array_fill_keys($itemIds, []);
        if ($itemIds === []) {
            return $result;
        }
        $rows = $database->fetchAll(
            'SELECT * FROM `' . self::TABLE . '` WHERE `section_id` = ? AND `item_id` IN (' . implode(',', array_fill(0, count($itemIds), '?')) . ')
              ORDER BY `item_id`, `field`, `position`, `file_id`',
            array_merge([$sectionId], $itemIds)
        );
        foreach ($rows as $row) {
            $row = self::decode($row);
            $result[$row['item_id']][$row['field']][] = $row;
        }
        return $result;
    }

    public static function url(array $file, bool $thumb = false): string
    {
        return WB_URL . '/modules/' . Module::DIR . '/file.php?f=' . $file['file_key'] . ($thumb && $file['kind'] === 'image' ? '&s=t' : '');
    }

    /**
     * Files of a field as the forms show them.
     *
     * @param list<array<string, mixed>> $files
     * @return list<array<string, mixed>>
     */
    public static function forForm(array $files): array
    {
        return array_map(static fn ($f) => $f + [
            'url'       => self::url($f),
            'thumb_url' => self::url($f, true),
            'size_text' => self::formatSize($f['size']),
        ], $files);
    }

    /**
     * Owners and admins see files of entries that are not public yet; the
     * forms that show them grant this to the session for a while.
     */
    public static function grant(int $itemId): void
    {
        $grants = array_filter((array) ($_SESSION['mod_listings_files'] ?? []), static fn ($t) => is_int($t) && $t > time() - self::GRANT_TTL);
        $grants[$itemId] = time();
        $_SESSION['mod_listings_files'] = $grants;
    }

    private static function granted(int $itemId): bool
    {
        $time = $_SESSION['mod_listings_files'][$itemId] ?? null;
        return is_int($time) && $time > time() - self::GRANT_TTL;
    }

    /**
     * Checks the uploads of a form and prepares the files (images encoded
     * again, files moved into the folder). Nothing is in the database yet:
     * store() records them once the entry is saved, discard() removes them
     * when saving fails.
     *
     * @param list<array<string, mixed>> $fields the fields the sender may edit
     * @param array<string, mixed>|null $uploads $_FILES['uploads']
     * The form names stored files by their key (as in their URL), never by ID.
     *
     * @param mixed $remove $_POST['files_remove']: keys of files to delete
     * @param mixed $order $_POST['files_order']: per field the keys in the new order, comma separated
     * @return array{0: array{new: list<array<string, mixed>>, remove: list<int>, order: array<int, int>}, 1: list<string>}
     */
    public static function check(int $sectionId, array $fields, ?int $itemId, ?array $uploads, mixed $remove, mixed $order = null): array
    {
        $existing = $itemId === null ? [] : self::forItem($sectionId, $itemId);
        $removeKeys = is_array($remove) ? array_values(array_filter($remove, static fn ($k) => is_string($k) && preg_match('/^[a-f0-9]{32}$/', $k) === 1)) : [];
        $plan = ['new' => [], 'remove' => [], 'order' => []];
        $errors = [];

        foreach ($fields as $field) {
            if (!self::isUpload($field['type'])) {
                continue;
            }
            $label = $field['label'] !== '' ? $field['label'] : $field['name'];
            $current = $existing[$field['name']] ?? [];
            $sent = self::uploadsOf($uploads, $field['name']);

            // the new order: keys of this entry and field only, the others keep their place behind them
            $wanted = is_array($order) && is_string($order[$field['name']] ?? null) ? explode(',', $order[$field['name']]) : [];
            $byKey = array_column($current, null, 'file_key');
            $sorted = [];
            foreach ($wanted as $key) {
                if (isset($byKey[$key]) && !in_array($byKey[$key], $sorted, true)) {
                    $sorted[] = $byKey[$key];
                }
            }
            foreach ($current as $file) {
                if (!in_array($file, $sorted, true)) {
                    $sorted[] = $file;
                }
            }
            foreach ($sorted as $position => $file) {
                if ($file['position'] !== $position + 1) {
                    $plan['order'][$file['file_id']] = $position + 1;
                }
            }

            // only files of this entry and field can be removed
            $gone = array_values(array_filter($current, static fn ($f) => in_array($f['file_key'], $removeKeys, true)));
            // a single image is replaced by a new one
            if ($field['type'] === 'image' && $sent !== []) {
                $gone = $current;
                $sent = array_slice($sent, 0, 1);
            }

            $count = count($current) - count($gone) + count($sent);
            if ($count > self::maxFiles($field)) {
                $errors[] = L_('MOD_LISTINGS:ERR_FILE_COUNT', $label, self::maxFiles($field));
                continue;
            }
            if ($count === 0 && $field['required']) {
                $errors[] = L_('MOD_LISTINGS:ERR_REQUIRED', $label);
                continue;
            }
            if (self::isImage($field['type']) && $sent !== [] && !self::imagesAvailable()) {
                $errors[] = L_('MOD_LISTINGS:ERR_NO_GD');
                continue;
            }
            foreach ($sent as $upload) {
                $error = match ($upload['error']) {
                    UPLOAD_ERR_OK => null,
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => L_('MOD_LISTINGS:ERR_FILE_SIZE', $upload['name'], self::formatSize(self::maxBytes($field))),
                    default => L_('MOD_LISTINGS:ERR_UPLOAD', $upload['name']),
                };
                if ($error === null && !is_uploaded_file($upload['tmp_name'])) {
                    $error = L_('MOD_LISTINGS:ERR_UPLOAD', $upload['name']);
                }
                if ($error === null) {
                    [$file, $error] = self::prepare($sectionId, $field, $upload['tmp_name'], $upload['name'], true);
                    if ($file !== null) {
                        $plan['new'][] = $file;
                    }
                }
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
            foreach ($gone as $file) {
                $plan['remove'][] = $file['file_id'];
            }
        }

        if ($errors !== []) {
            self::discard($plan);
            return [['new' => [], 'remove' => [], 'order' => []], $errors];
        }
        return [$plan, []];
    }

    /** Records the prepared files of an entry, applies the new order and deletes the removed ones. */
    public static function store(int $sectionId, int $itemId, array $plan): bool
    {
        global $database;
        $ok = true;
        foreach ($plan['order'] ?? [] as $fileId => $position) {
            $database->query(
                'UPDATE `' . self::TABLE . '` SET `position` = ? WHERE `file_id` = ? AND `item_id` = ? AND `section_id` = ?',
                [$position, $fileId, $itemId, $sectionId]
            );
            $ok = !$database->hasError() && $ok;
        }
        foreach ($plan['new'] as $file) {
            $position = (int) $database->fetchValue(
                'SELECT MAX(`position`) FROM `' . self::TABLE . '` WHERE `item_id` = ? AND `field` = ?',
                [$itemId, $file['field']]
            ) + 1;
            $database->insertRow(self::TABLE, [
                'section_id' => $sectionId,
                'item_id'    => $itemId,
                'field'      => $file['field'],
                'kind'       => $file['kind'],
                'file_key'   => $file['file_key'],
                'ext'        => $file['ext'],
                'filename'   => $file['filename'],
                'mime'       => $file['mime'],
                'size'       => $file['size'],
                'width'      => $file['width'],
                'height'     => $file['height'],
                'position'   => $position,
                'downloads'  => 0,
                'created_at' => time(),
            ]);
            if ($database->hasError()) {
                $ok = false;
                self::unlinkFiles($sectionId, $file);
            }
        }
        foreach ($plan['remove'] as $fileId) {
            $ok = self::delete($sectionId, $itemId, (int) $fileId) && $ok;
        }
        return $ok;
    }

    /** Removes prepared files that were never recorded. */
    public static function discard(array $plan): void
    {
        foreach ($plan['new'] ?? [] as $file) {
            self::unlinkFiles($file['section_id'], $file);
        }
    }

    public static function delete(int $sectionId, int $itemId, int $fileId): bool
    {
        global $database;
        $row = $database->fetchRow(
            'SELECT * FROM `' . self::TABLE . '` WHERE `file_id` = ? AND `item_id` = ? AND `section_id` = ?',
            [$fileId, $itemId, $sectionId]
        );
        if ($row === null) {
            return true;
        }
        $database->query('DELETE FROM `' . self::TABLE . '` WHERE `file_id` = ?', [$fileId]);
        if ($database->hasError()) {
            return false;
        }
        self::unlinkFiles($sectionId, self::decode($row));
        return true;
    }

    public static function deleteForItem(int $sectionId, int $itemId): void
    {
        global $database;
        $rows = $database->fetchAll(
            'SELECT * FROM `' . self::TABLE . '` WHERE `item_id` = ? AND `section_id` = ?',
            [$itemId, $sectionId]
        );
        $database->query('DELETE FROM `' . self::TABLE . '` WHERE `item_id` = ? AND `section_id` = ?', [$itemId, $sectionId]);
        if (!$database->hasError()) {
            foreach ($rows as $row) {
                self::unlinkFiles($sectionId, self::decode($row));
            }
        }
    }

    /** The section is deleted: its rows and its folder. */
    public static function deleteSection(int $sectionId): void
    {
        global $database;
        $database->query('DELETE FROM `' . self::TABLE . '` WHERE `section_id` = ?', [$sectionId]);
        $dir = self::baseDir() . '/' . $sectionId;
        if (is_dir($dir)) {
            rm_full_dir($dir);
        }
    }

    /**
     * Takes a file from the disk (the wbProfiles import) as if it had been uploaded.
     *
     * @return array{0: ?array<string, mixed>, 1: ?string} [prepared file, error]
     */
    public static function fromPath(int $sectionId, array $field, string $path, string $name): array
    {
        return self::prepare($sectionId, $field, $path, $name, false);
    }

    /**
     * Delivers a file for file.php: f is the key, s=t the thumbnail.
     * Unknown, hidden or not permitted files are a 404, never a 403, so
     * nobody learns that a key exists.
     */
    public static function serve(string $key, bool $thumb): never
    {
        global $database;
        $notFound = static function (): never {
            http_response_code(404);
            exit;
        };
        preg_match('/^[a-f0-9]{32}$/', $key) === 1 || $notFound();
        $row = $database->fetchRow('SELECT * FROM `' . self::TABLE . '` WHERE `file_key` = ?', [$key]) ?? $notFound();
        $file = self::decode($row);
        $item = Items::find($file['section_id'], $file['item_id']) ?? $notFound();

        $public = self::isPublic($file, $item);
        ($public || self::granted($item['item_id'])) || $notFound();

        $path = self::path($file['section_id'], $file, $thumb && $file['kind'] === 'image');
        if (!is_file($path)) {
            $path = self::path($file['section_id'], $file, false);
        }
        is_file($path) || $notFound();

        $modified = (int) filemtime($path);
        $etag = '"' . $key . ($thumb ? 't' : '') . '-' . $modified . '"';
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; sandbox");
        header('Cache-Control: ' . ($public ? 'public, max-age=86400' : 'private, no-store'));
        if ($public) {
            header('ETag: ' . $etag);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');
            if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
                http_response_code(304);
                exit;
            }
        }

        if ($file['kind'] === 'image') {
            header('Content-Type: ' . $file['mime']);
            header('Content-Disposition: inline; filename*=UTF-8\'\'' . rawurlencode($file['filename']));
        } else {
            // files are always downloads: the browser does not render them as a page of this site
            header('Content-Type: ' . ($file['mime'] !== '' ? $file['mime'] : 'application/octet-stream'));
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($file['filename']));
            if ($public) {
                $database->query('UPDATE `' . self::TABLE . '` SET `downloads` = `downloads` + 1 WHERE `file_id` = ?', [$file['file_id']]);
            }
        }
        header('Content-Length: ' . filesize($path));
        // the session is not needed any more; an open one would block the visitor's next request
        session_write_close();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        readfile($path);
        exit;
    }

    /**
     * A visitor may see the file: the entry is published and not expired,
     * the field exists and is public, and the page and section are visible.
     */
    private static function isPublic(array $file, array $item): bool
    {
        if ($item['status'] !== 'published' || ($item['expires_at'] > 0 && $item['expires_at'] <= time())) {
            return false;
        }
        $field = null;
        foreach (Fields::forSection($file['section_id']) as $f) {
            if ($f['name'] === $file['field']) {
                $field = $f;
                break;
            }
        }
        if ($field === null || $field['visibility'] !== 'public' || !self::isUpload($field['type'])) {
            return false;
        }
        return Module::visiblePage($file['section_id']) !== null;
    }

    /**
     * Checks one file and puts it into the section's folder.
     *
     * @return array{0: ?array<string, mixed>, 1: ?string} [prepared file, error]
     */
    private static function prepare(int $sectionId, array $field, string $source, string $name, bool $uploaded): array
    {
        $filename = self::cleanName($name);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $size = (int) @filesize($source);
        if ($size <= 0) {
            return [null, L_('MOD_LISTINGS:ERR_UPLOAD', $filename)];
        }
        // the upload limit is for visitors; an import takes what wbProfiles had
        if ($uploaded && $size > self::maxBytes($field)) {
            return [null, L_('MOD_LISTINGS:ERR_FILE_SIZE', $filename, self::formatSize(self::maxBytes($field)))];
        }
        if (!in_array($ext, self::extensions($field), true)) {
            return [null, L_('MOD_LISTINGS:ERR_FILE_TYPE', $filename, implode(', ', self::extensions($field)))];
        }
        $mime = self::mime($source, $ext);

        $dir = self::dir($sectionId);
        if ($dir === null) {
            return [null, L_('MOD_LISTINGS:ERR_UPLOAD_DIR')];
        }
        $file = [
            'section_id' => $sectionId,
            'field'      => $field['name'],
            'kind'       => self::isImage($field['type']) ? 'image' : 'file',
            'file_key'   => bin2hex(random_bytes(16)),
            'filename'   => $filename,
            'mime'       => $mime,
            'width'      => 0,
            'height'     => 0,
        ];

        if ($file['kind'] === 'image') {
            if (!isset(self::IMAGE_MIMES[$mime])) {
                return [null, L_('MOD_LISTINGS:ERR_FILE_TYPE', $filename, implode(', ', self::extensions($field)))];
            }
            $file['ext'] = self::IMAGE_MIMES[$mime];
            $error = self::encodeImage($source, $file, self::maxPx($field), $dir);
            if ($error !== null) {
                self::unlinkFiles($sectionId, $file);
                return [null, $error];
            }
            $file['size'] = (int) filesize(self::path($sectionId, $file, false));
            // the name the visitor saves the image under follows the stored format
            $file['filename'] = pathinfo($filename, PATHINFO_FILENAME) . '.' . $file['ext'];
            return [$file, null];
        }

        $file['ext'] = $ext;
        $target = self::path($sectionId, $file, false);
        $moved = $uploaded ? move_uploaded_file($source, $target) : copy($source, $target);
        if (!$moved) {
            return [null, L_('MOD_LISTINGS:ERR_UPLOAD', $filename)];
        }
        @chmod($target, 0644);
        $file['size'] = (int) filesize($target);
        return [$file, null];
    }

    /** Decodes, turns (EXIF orientation), scales and encodes again; writes image and thumbnail. */
    private static function encodeImage(string $source, array &$file, int $maxPx, string $dir): ?string
    {
        $name = $file['filename'];
        $info = @getimagesize($source);
        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            return L_('MOD_LISTINGS:ERR_FILE_TYPE', $name, 'jpg, png, gif, webp');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            return L_('MOD_LISTINGS:ERR_IMAGE_PIXELS', $name);
        }
        $image = match ($file['ext']) {
            'jpg'  => @imagecreatefromjpeg($source),
            'png'  => @imagecreatefrompng($source),
            'gif'  => function_exists('imagecreatefromgif') ? @imagecreatefromgif($source) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
        if (!$image instanceof \GdImage) {
            return L_('MOD_LISTINGS:ERR_FILE_TYPE', $name, 'jpg, png, gif, webp');
        }
        if ($file['ext'] === 'jpg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($source);
            $angle = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;
            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);
                if ($rotated instanceof \GdImage) {
                    $image = $rotated;
                }
            }
        }
        $full = self::scaled($image, $maxPx);
        $thumb = self::scaled($image, self::THUMB_PX);
        $file['width'] = imagesx($full);
        $file['height'] = imagesy($full);
        $ok = self::writeImage($full, $file['ext'], self::path($file['section_id'], $file, false))
            && self::writeImage($thumb, $file['ext'], self::path($file['section_id'], $file, true));
        return $ok ? null : L_('MOD_LISTINGS:ERR_UPLOAD', $name);
    }

    /** A copy that fits into $max × $max; always a new true-colour image with transparency kept. */
    private static function scaled(\GdImage $image, int $max): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $ratio = min(1, $max / max($w, $h));
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));
        $copy = imagecreatetruecolor($nw, $nh);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        imagefill($copy, 0, 0, imagecolorallocatealpha($copy, 0, 0, 0, 127));
        imagecopyresampled($copy, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $copy;
    }

    private static function writeImage(\GdImage $image, string $ext, string $path): bool
    {
        $ok = match ($ext) {
            'jpg'  => imagejpeg($image, $path, 85),
            'png'  => imagepng($image, $path, 6),
            'gif'  => imagegif($image, $path),
            'webp' => function_exists('imagewebp') && imagewebp($image, $path, 85),
            default => false,
        };
        if ($ok) {
            @chmod($path, 0644);
        }
        return $ok;
    }

    private static function mime(string $path, string $ext = ''): string
    {
        if (class_exists('finfo')) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            if (is_string($mime) && $mime !== '') {
                return substr($mime, 0, 100);
            }
        }
        // without fileinfo: images are still recognised by their content
        $info = @getimagesize($path);
        if (is_array($info) && is_string($info['mime'] ?? null)) {
            return $info['mime'];
        }
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return substr($mime, 0, 100);
            }
        }
        // the type only tells the browser what it downloads; files are always sent as attachment
        return self::MIME_BY_EXT[$ext] ?? 'application/octet-stream';
    }

    /** The name shown and offered for download; never used as a path. */
    private static function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = trim((string) preg_replace('/[\x00-\x1F\x7F"<>:|?*\/\\\\]+/u', '', $name));
        if (mb_strlen($name) > 200) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $name = mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 190) . ($ext !== '' ? '.' . $ext : '');
        }
        return $name !== '' && $name[0] !== '.' ? $name : 'file' . $name;
    }

    private static function baseDir(): string
    {
        return WB_PATH . MEDIA_DIRECTORY . '/' . Module::DIR;
    }

    /** The section's folder, created and closed to direct access on first use. */
    private static function dir(int $sectionId): ?string
    {
        $base = self::baseDir();
        $dir = $base . '/' . $sectionId;
        if (!is_dir($dir) && !make_dir($dir) && !is_dir($dir)) {
            return null;
        }
        foreach ([$base, $dir] as $folder) {
            if (!is_file($folder . '/.htaccess')) {
                // Apache 2.4 and 2.2; other servers need an own rule (see README)
                file_put_contents($folder . '/.htaccess', "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
            }
            if (!is_file($folder . '/index.php')) {
                file_put_contents($folder . '/index.php', "<?php\nheader('Location: ../../index.php');\n");
            }
        }
        return $dir;
    }

    private static function path(int $sectionId, array $file, bool $thumb): string
    {
        return self::baseDir() . '/' . $sectionId . '/' . $file['file_key'] . ($thumb ? '_t' : '') . '.' . $file['ext'];
    }

    private static function unlinkFiles(int $sectionId, array $file): void
    {
        foreach ([false, true] as $thumb) {
            $path = self::path($sectionId, $file, $thumb);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * $_FILES['uploads'][…][<field>][] as a list of uploads; no file chosen is left out.
     *
     * @return list<array{name: string, tmp_name: string, error: int}>
     */
    private static function uploadsOf(?array $uploads, string $field): array
    {
        $names = $uploads['name'][$field] ?? null;
        if (!is_array($names)) {
            return [];
        }
        $list = [];
        foreach ($names as $i => $name) {
            $error = (int) ($uploads['error'][$field][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE || !is_string($name)) {
                continue;
            }
            $list[] = ['name' => $name, 'tmp_name' => (string) ($uploads['tmp_name'][$field][$i] ?? ''), 'error' => $error];
        }
        return $list;
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0' || $value === '-1') {
            return 0;
        }
        $number = (float) $value;
        return (int) match (strtolower(substr($value, -1))) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private static function decode(array $row): array
    {
        foreach (['file_id', 'section_id', 'item_id', 'size', 'width', 'height', 'position', 'downloads', 'created_at'] as $key) {
            $row[$key] = (int) ($row[$key] ?? 0);
        }
        return $row;
    }
}
