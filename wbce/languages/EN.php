<?php
/**
 * Listings – English strings (always loaded first).
 * @module listings  @author Bianka Martinovic
 * @license GNU General Public License   @platform WBCE 1.7.0+
 */

defined('WB_PATH') or die('This file cannot be accessed directly!');

$module_description = 'Directories of entries with their own fields: member lists, classifieds, job boards, add-on repositories.';

// tabs and toolbar
$MOD_LISTINGS['TAB_ITEMS']    = 'Entries';
$MOD_LISTINGS['TAB_FIELDS']   = 'Fields';
$MOD_LISTINGS['TAB_LAYOUTS']  = 'Layouts';
$MOD_LISTINGS['TAB_SETTINGS'] = 'Settings';
$MOD_LISTINGS['TABS']         = 'Areas';
$MOD_LISTINGS['WAITING_COUNT'] = '%s waiting for approval';
$MOD_LISTINGS['NEW_ITEM']     = 'New entry';
$MOD_LISTINGS['EDIT_ITEM']    = 'Edit "%s"';
$MOD_LISTINGS['NEW_FIELD']    = 'New field';
$MOD_LISTINGS['EDIT_FIELD']   = 'Edit field "%s"';
$MOD_LISTINGS['ALL']          = 'All';
$MOD_LISTINGS['SAVE_CONTINUE'] = 'Save and continue editing';

// entries
$MOD_LISTINGS['TITLE']            = 'Title';
$MOD_LISTINGS['SLUG']             = 'Address (slug)';
$MOD_LISTINGS['STATUS']           = 'Status';
$MOD_LISTINGS['STATUS_DRAFT']     = 'Draft';
$MOD_LISTINGS['STATUS_PUBLISHED'] = 'Published';
$MOD_LISTINGS['CREATED']          = 'Created';
$MOD_LISTINGS['UPDATED']          = 'Updated';
$MOD_LISTINGS['EXPIRES']          = 'Expires';
$MOD_LISTINGS['PUBLICATION']      = 'Publication';
$MOD_LISTINGS['NO_ENTRIES']       = 'No entries yet.';
$MOD_LISTINGS['HELP_EXPIRES']     = 'Empty: never. From the day after, the entry is no longer shown.';
$MOD_LISTINGS['HELP_SLUG']        = 'Part of the address of the entry. Empty: made from the title.';
$MOD_LISTINGS['HINT_NO_FIELDS']   = 'This directory has no fields yet; entries have a title only.';
$MOD_LISTINGS['CONFIRM_DELETE_ITEM'] = 'Delete the entry "%s"? This cannot be undone.';

// fields
$MOD_LISTINGS['HINT_FIELDS']       = 'The fields every entry of this directory has, in the order of the form.';
$MOD_LISTINGS['NO_FIELDS']         = 'No fields yet.';
$MOD_LISTINGS['FIELD_LABEL']       = 'Label';
$MOD_LISTINGS['FIELD_NAME']        = 'Name';
$MOD_LISTINGS['FIELD_TYPE']        = 'Type';
$MOD_LISTINGS['FIELD_HELP']        = 'Help text';
$MOD_LISTINGS['FIELD_OPTIONS']     = 'Options';
$MOD_LISTINGS['FIELD_REQUIRED']    = 'Required';
$MOD_LISTINGS['FIELD_IN_LIST']     = 'Show in list';
$MOD_LISTINGS['FIELD_VISIBILITY']  = 'Visible';
$MOD_LISTINGS['FIELD_CHOICES']     = 'Choices';
$MOD_LISTINGS['FIELD_MAXLENGTH']   = 'Maximum length';
$MOD_LISTINGS['FIELD_RANGE']       = 'Range';
$MOD_LISTINGS['HELP_NAME']         = 'Used in layouts as [ITEM.NAME]. a–z, 0–9 and _, starting with a letter. Empty: made from the label.';
$MOD_LISTINGS['HELP_NAME_LOCKED']  = 'The name cannot be changed any more because entries store their values under it.';
$MOD_LISTINGS['HELP_CHOICES']      = 'One choice per line. "value|Label" stores the value and shows the label.';
$MOD_LISTINGS['VISIBILITY_PUBLIC'] = 'Public';
$MOD_LISTINGS['VISIBILITY_ADMIN']  = 'Admin only';
$MOD_LISTINGS['MOVE_UP']           = 'Move up';
$MOD_LISTINGS['MOVE_DOWN']         = 'Move down';
$MOD_LISTINGS['CONFIRM_DELETE_FIELD'] = 'Delete the field "%s"? Values already entered are kept but no longer shown.';

$MOD_LISTINGS['TYPE_TEXT']       = 'Text';
$MOD_LISTINGS['TYPE_TEXTAREA']   = 'Text, several lines';
$MOD_LISTINGS['TYPE_NUMBER']     = 'Number';
$MOD_LISTINGS['TYPE_EMAIL']      = 'E-mail address';
$MOD_LISTINGS['TYPE_URL']        = 'Web address';
$MOD_LISTINGS['TYPE_PHONE']      = 'Phone number';
$MOD_LISTINGS['TYPE_DATE']       = 'Date';
$MOD_LISTINGS['TYPE_DATETIME']   = 'Date and time';
$MOD_LISTINGS['TYPE_SELECT']     = 'Selection list';
$MOD_LISTINGS['TYPE_RADIO']      = 'One of several (radio buttons)';
$MOD_LISTINGS['TYPE_CHECKBOXES'] = 'Several of several (checkboxes)';
$MOD_LISTINGS['TYPE_BOOLEAN']    = 'Yes/no';
$MOD_LISTINGS['TYPE_HEADING']    = 'Heading in the form';

// layouts and settings
$MOD_LISTINGS['HINT_LAYOUTS']      = 'Layouts use the LayoutParser syntax: [KEY], {foreach}, {if}. Values are escaped automatically. They cannot run PHP.';
$MOD_LISTINGS['LAYOUT_FIELD_KEYS'] = 'Fields of this directory:';
$MOD_LISTINGS['LAYOUT_LIST']       = 'List';
$MOD_LISTINGS['LAYOUT_DETAIL']     = 'Detail';
$MOD_LISTINGS['LAYOUT_DEFAULT']    = 'default';
$MOD_LISTINGS['LAYOUT_CUSTOM']     = 'changed';
$MOD_LISTINGS['LAYOUT_RESET']      = 'Restore the default layout';
$MOD_LISTINGS['PER_PAGE']          = 'Entries per page';
$MOD_LISTINGS['SORT']              = 'Order';
$MOD_LISTINGS['SORT_NEWEST']       = 'Newest first';
$MOD_LISTINGS['SORT_PUBLISHED']    = 'Latest published first';
$MOD_LISTINGS['SORT_UPDATED']      = 'Last updated first';
$MOD_LISTINGS['SORT_TITLE']        = 'Title A–Z';

// frontend
$MOD_LISTINGS['MORE']         = 'Details';
$MOD_LISTINGS['PREVIOUS']     = 'Previous';
$MOD_LISTINGS['NEXT']         = 'Next';
$MOD_LISTINGS['BACK_TO_LIST'] = 'Back to the list';
$MOD_LISTINGS['NOT_FOUND']    = 'This entry does not exist or is no longer available.';
$MOD_LISTINGS['YES']          = 'Yes';

// 0.2: statuses, owners, approval
$MOD_LISTINGS['STATUS_PENDING']     = 'Waiting for approval';
$MOD_LISTINGS['STATUS_REJECTED']    = 'Rejected';
$MOD_LISTINGS['STATUS_UNCONFIRMED'] = 'Not confirmed';
$MOD_LISTINGS['APPROVE']            = 'Approve';
$MOD_LISTINGS['REJECT']             = 'Reject';
$MOD_LISTINGS['OWNER_EMAIL']        = 'E-mail address';
$MOD_LISTINGS['HELP_OWNER_EMAIL']   = 'For the confirmation and for later changes. It is not shown on the website.';
$MOD_LISTINGS['HELP_OWNER_EMAIL_ADMIN'] = 'Owner of the entry: receives edit links and the decision on approval. Empty: only the admin edits.';
$MOD_LISTINGS['VISIBILITY_OWNER']   = 'Owner and admin';
$MOD_LISTINGS['FIELD_EDITABLE']     = 'The owner may edit it';
$MOD_LISTINGS['FIELD_EDITABLE_SHORT'] = 'Owner';
$MOD_LISTINGS['HELP_EDITABLE']      = 'Off: shown in the frontend form neither for new entries nor for changes, e.g. for a "checked" mark.';
$MOD_LISTINGS['MSG_APPROVED']       = 'Entry "%s" approved.';
$MOD_LISTINGS['MSG_REJECTED']       = 'Entry "%s" rejected.';
$MOD_LISTINGS['MSG_MAIL_FAILED']    = 'The mail to the owner could not be sent.';

// settings 0.2
$MOD_LISTINGS['SETTINGS_LIST']      = 'List';
$MOD_LISTINGS['SETTINGS_FRONTEND']  = 'Entries by visitors';
$MOD_LISTINGS['FRONTEND']           = 'Frontend';
$MOD_LISTINGS['FRONTEND_ON']        = 'Visitors may add and edit entries';
$MOD_LISTINGS['HELP_FRONTEND']      = 'Without an account: the e-mail address is confirmed by a link, later changes go through an edit link sent by mail.';
$MOD_LISTINGS['APPROVAL']           = 'Approval';
$MOD_LISTINGS['APPROVAL_ON']        = 'New entries wait for approval by the admin';
$MOD_LISTINGS['RECHECK_ON']         = 'Changes to a published entry are checked again';
$MOD_LISTINGS['NOTIFY_EMAIL']       = 'Notify';
$MOD_LISTINGS['HELP_NOTIFY_EMAIL']  = 'This address is told about new confirmed entries. Empty: no notification.';
$MOD_LISTINGS['SPAM']               = 'Spam protection';
$MOD_LISTINGS['CAPTCHA_ON']         = 'Captcha in the forms';
$MOD_LISTINGS['HELP_CAPTCHA']       = 'The captcha of "Captcha & spam protection". A hidden field and a minimum time are always checked.';
$MOD_LISTINGS['HELP_NO_CAPTCHA']    = 'The module "Captcha & spam protection" is not installed.';

// frontend 0.2
$MOD_LISTINGS['ADD_ENTRY']          = 'Add an entry';
$MOD_LISTINGS['NEW_ENTRY']          = 'New entry';
$MOD_LISTINGS['REQUEST_LINK']       = 'Change your entry';
$MOD_LISTINGS['INTRO_NEW']          = 'After sending you receive an e-mail with a link. The entry appears once you have confirmed it.';
$MOD_LISTINGS['INTRO_NEW_APPROVAL'] = 'After sending you receive an e-mail with a link. Once you have confirmed it, the entry is checked and then published.';
$MOD_LISTINGS['INTRO_REQUEST_LINK'] = 'Enter the address you used for your entry. We send you a link to change or delete it, valid for one hour.';
$MOD_LISTINGS['INTRO_STATUS_DRAFT']     = 'This entry is not confirmed yet.';
$MOD_LISTINGS['INTRO_STATUS_PENDING']   = 'This entry is waiting for approval.';
$MOD_LISTINGS['INTRO_STATUS_PUBLISHED'] = 'This entry is published.';
$MOD_LISTINGS['INTRO_STATUS_REJECTED']  = 'This entry was not approved. After a change it is checked again.';
$MOD_LISTINGS['INTRO_CONFIRM']      = 'Please confirm that you added this entry and that your e-mail address may be stored for it.';
$MOD_LISTINGS['CONFIRM_ENTRY']      = 'Confirm entry';
$MOD_LISTINGS['CONFIRM_BUTTON']     = 'Confirm';
$MOD_LISTINGS['SUBMIT_NEW']         = 'Send';
$MOD_LISTINGS['SEND_LINK']          = 'Send link';
$MOD_LISTINGS['DELETE_ENTRY']       = 'Delete this entry';
$MOD_LISTINGS['NOT_PUBLIC']         = 'not public';
$MOD_LISTINGS['PRIVACY_CONSENT']    = 'I agree that my details are stored for this entry. I can delete the entry at any time.';
$MOD_LISTINGS['LINK_INVALID']       = 'This link is not valid (any more). Links in our mails can be used for a limited time.';
$MOD_LISTINGS['MSG_CHECK_MAIL']     = 'Thank you! Please confirm your entry with the link in the e-mail we just sent you.';
$MOD_LISTINGS['MSG_CONFIRMED_WAITING']   = 'Thank you, your entry is confirmed. It is published as soon as it has been checked.';
$MOD_LISTINGS['MSG_CONFIRMED_PUBLISHED'] = 'Thank you, your entry is confirmed and published.';
$MOD_LISTINGS['MSG_LINK_SENT']      = 'If there are entries for this address, a link to change them is on its way.';
$MOD_LISTINGS['MSG_SAVED']          = 'Your changes are saved.';
$MOD_LISTINGS['MSG_SAVED_WAITING']  = 'Your changes are saved. The entry is published again once it has been checked.';
$MOD_LISTINGS['MSG_DELETED']        = 'Your entry has been deleted.';
$MOD_LISTINGS['ERR_PRIVACY']        = 'Please agree to the storage of your details.';
$MOD_LISTINGS['ERR_TOO_MANY']       = 'Too many requests for this address. Please try again in an hour.';
$MOD_LISTINGS['ERR_REJECTED']       = 'The form could not be processed. Please fill it in again.';
$MOD_LISTINGS['ERR_CAPTCHA']        = 'The security check was not solved correctly.';

// mails (plain text)
$MOD_LISTINGS['MAIL_CONFIRM_SUBJECT'] = 'Please confirm your entry – %s';
$MOD_LISTINGS['MAIL_CONFIRM_TEXT']    = "Someone added an entry with this e-mail address to \"%1\$s\" on %2\$s.\n\nTo confirm it, open this link (valid for %4\$d hours):\n%3\$s";
$MOD_LISTINGS['MAIL_EDIT_SUBJECT']    = 'Change your entry – %s';
$MOD_LISTINGS['MAIL_EDIT_TEXT']       = "You asked for a link to change your entry in \"%s\". Each link is valid for %s minutes:";
$MOD_LISTINGS['MAIL_IGNORE']          = 'If this was not you, ignore this mail; nothing happens without the link.';
$MOD_LISTINGS['MAIL_FOOTER']          = '-- ' . "\n" . '%s';
$MOD_LISTINGS['MAIL_PUBLISHED_SUBJECT'] = 'Your entry "%s" is published';
$MOD_LISTINGS['MAIL_PUBLISHED_TEXT']    = 'Your entry "%s" in "%s" has been approved and is now online:';
$MOD_LISTINGS['MAIL_REJECTED_SUBJECT']  = 'Your entry "%s" was not approved';
$MOD_LISTINGS['MAIL_REJECTED_TEXT']     = 'Your entry "%s" in "%s" was not approved. You can change it with an edit link from the website; it is then checked again.';
$MOD_LISTINGS['MAIL_ADMIN_WAITING_SUBJECT'] = 'New entry waiting for approval: %s';
$MOD_LISTINGS['MAIL_ADMIN_WAITING_TEXT']    = 'An entry in "%s" was confirmed and is waiting for approval:';
$MOD_LISTINGS['MAIL_ADMIN_NEW_SUBJECT']     = 'New entry: %s';
$MOD_LISTINGS['MAIL_ADMIN_NEW_TEXT']        = 'An entry in "%s" was confirmed and is online:';

// 0.3: accounts, runtime, contact
$MOD_LISTINGS['OWNER_MODE']         = 'Owners';
$MOD_LISTINGS['OWNER_MODE_EMAIL']   = 'Without an account: links by e-mail';
$MOD_LISTINGS['OWNER_MODE_ACCOUNT'] = 'With a frontend account of the website';
$MOD_LISTINGS['HELP_OWNER_MODE']    = 'With an account owners log in, find their entries under "My entries" and need no confirmation link.';
$MOD_LISTINGS['WARN_NO_LOGIN']      = 'The frontend login of the website is switched off (Settings). Owners with an account cannot log in.';
$MOD_LISTINGS['SETTINGS_RUNTIME']   = 'Runtime';
$MOD_LISTINGS['RUNTIME_DAYS']       = 'Runtime in days';
$MOD_LISTINGS['HELP_RUNTIME']       = 'Entries of owners expire this many days after their first publication. 0: never. Your own entries keep the date set in the entry.';
$MOD_LISTINGS['REMIND_DAYS']        = 'Reminder (days before)';
$MOD_LISTINGS['HELP_REMIND']        = 'The owner gets a mail with a link to extend. From then on, and after the expiry, the entry can be extended by one runtime. 0: no reminder, extending only after the expiry.';
$MOD_LISTINGS['SETTINGS_CONTACT']   = 'Contact';
$MOD_LISTINGS['CONTACT']            = 'Contact form';
$MOD_LISTINGS['CONTACT_ON']         = 'Visitors may write to the owner from the detail page';
$MOD_LISTINGS['HELP_CONTACT']       = 'The address of the owner stays hidden; the answer goes straight to the sender. Only for entries with an owner address.';
$MOD_LISTINGS['ERR_RUNTIME']        = 'The runtime must be a number from 0 to %s.';
$MOD_LISTINGS['ERR_REMIND']         = 'The reminder must be a number from 0 to 365.';

$MOD_LISTINGS['INTRO_NEW_ACCOUNT']          = 'The entry is published right after sending. You find it under "My entries".';
$MOD_LISTINGS['INTRO_NEW_ACCOUNT_APPROVAL'] = 'After sending the entry is checked and then published. You find it under "My entries".';
$MOD_LISTINGS['MY_ENTRIES']        = 'My entries';
$MOD_LISTINGS['NO_OWN_ENTRIES']    = 'You have no entries here yet.';
$MOD_LISTINGS['ACTIONS']           = 'Actions';
$MOD_LISTINGS['LOGIN_REQUIRED']    = 'Please log in';
$MOD_LISTINGS['INTRO_LOGIN']       = 'Entries here belong to an account of this website. Log in to add or change entries.';
$MOD_LISTINGS['LOGIN']             = 'Log in';
$MOD_LISTINGS['SIGNUP']            = 'Create an account';
$MOD_LISTINGS['ERR_NO_LOGIN']      = 'Logging in is switched off on this website, so entries cannot be added here.';
$MOD_LISTINGS['EXPIRES_ON']        = 'It runs until %s.';
$MOD_LISTINGS['EXPIRED_ON']        = 'It expired on %s and is no longer shown.';
$MOD_LISTINGS['EXPIRED']           = 'expired';
$MOD_LISTINGS['EXTEND']            = 'Extend';
$MOD_LISTINGS['EXTEND_ENTRY']      = 'Extend "%s"';
$MOD_LISTINGS['MSG_EXTENDED']      = 'The entry is extended until %s.';
$MOD_LISTINGS['MSG_ACCOUNT_WAITING']   = 'Thank you! Your entry is published as soon as it has been checked.';
$MOD_LISTINGS['MSG_ACCOUNT_PUBLISHED'] = 'Thank you! Your entry is published.';
$MOD_LISTINGS['ERR_NOT_YOURS']         = 'This entry does not exist or does not belong to your account.';
$MOD_LISTINGS['ERR_TOO_MANY_CONTACT']  = 'You have sent several messages in a short time. Please try again in an hour.';
$MOD_LISTINGS['ERR_NOT_EXTENDABLE'] = 'This entry cannot be extended (yet).';

$MOD_LISTINGS['CONTACT_TITLE']     = 'Contact';
$MOD_LISTINGS['CONTACT_NAME']      = 'Your name';
$MOD_LISTINGS['CONTACT_EMAIL']     = 'Your e-mail address';
$MOD_LISTINGS['CONTACT_MESSAGE']   = 'Message';
$MOD_LISTINGS['CONTACT_CONSENT']   = 'I agree that my name, address and message are passed on to the owner of this entry.';
$MOD_LISTINGS['CONTACT_SEND']      = 'Send message';
$MOD_LISTINGS['ERR_CONTACT_SHORT'] = 'Please write a few more words (at least 10 characters).';
$MOD_LISTINGS['MSG_CONTACT_SENT']  = 'Thank you, your message is on its way.';

$MOD_LISTINGS['MAIL_REMINDER_SUBJECT'] = 'Your entry "%1$s" expires on %2$s';
$MOD_LISTINGS['MAIL_REMINDER_TEXT']    = 'Your entry "%1$s" in "%2$s" expires on %3$s and is then no longer shown. To keep it online, extend it here:';
$MOD_LISTINGS['MAIL_CONTACT_SUBJECT']  = 'Message about your entry "%s"';
$MOD_LISTINGS['MAIL_CONTACT_TEXT']     = 'Someone wrote to you about your entry "%1$s" in "%2$s" on %3$s. Your address was not shown; to answer, simply reply to this mail.';

// 0.4: categories, tags, filter, feed
$MOD_LISTINGS['TAB_CATEGORIES']       = 'Categories';
$MOD_LISTINGS['CATEGORIES']           = 'Categories';
$MOD_LISTINGS['CATEGORY']             = 'Category';
$MOD_LISTINGS['NEW_CATEGORY']         = 'New category';
$MOD_LISTINGS['NEW_SUBCATEGORY']      = 'Subcategory';
$MOD_LISTINGS['EDIT_CATEGORY']        = 'Edit category "%s"';
$MOD_LISTINGS['CATEGORY_NAME']        = 'Name';
$MOD_LISTINGS['CATEGORY_PARENT']      = 'Below';
$MOD_LISTINGS['CATEGORY_TOP']         = '(top level)';
$MOD_LISTINGS['CATEGORY_DESCRIPTION'] = 'Description';
$MOD_LISTINGS['CATEGORY_VISIBLE']     = 'Visible';
$MOD_LISTINGS['CATEGORY_VISIBLE_ON']  = 'Shown on the website, offered in filter and forms';
$MOD_LISTINGS['HELP_CATEGORY_SLUG']   = 'Part of the address of the filtered list. Empty: made from the name.';
$MOD_LISTINGS['HINT_NO_CATEGORIES']   = 'No categories yet. Without categories the list has no category filter.';
$MOD_LISTINGS['CONFIRM_DELETE_CATEGORY'] = 'Delete the category "%s"? Its subcategories move up one level, the entries stay.';
$MOD_LISTINGS['ORDER']                = 'Order';
$MOD_LISTINGS['ENTRIES']              = 'Entries';
$MOD_LISTINGS['NO']                   = 'No';
$MOD_LISTINGS['HIDDEN']               = 'hidden';
$MOD_LISTINGS['TAGS']                 = 'Tags';
$MOD_LISTINGS['TAG']                  = 'Tag';
$MOD_LISTINGS['NO_TAGS']              = 'No tags yet.';
$MOD_LISTINGS['RENAME']               = 'Rename';
$MOD_LISTINGS['CONFIRM_DELETE_TAG']   = 'Delete the tag "%s"? The entries stay.';
$MOD_LISTINGS['HINT_TAGS']            = 'Tags come into being when they are entered at an entry.';
$MOD_LISTINGS['HINT_TAGS_OFF']        = 'Tags are switched off in the settings; they are neither shown nor offered.';
$MOD_LISTINGS['HELP_TAGS']            = 'Separated by commas.';
$MOD_LISTINGS['TAGS_MODE_OFF']        = 'Off';
$MOD_LISTINGS['TAGS_MODE_FIXED']      = 'On, owners choose from the existing tags';
$MOD_LISTINGS['TAGS_MODE_OPEN']       = 'On, owners may add new tags';
$MOD_LISTINGS['FIELD_FILTERABLE']     = 'Offer as filter above the list';
$MOD_LISTINGS['HELP_FILTERABLE']      = 'Only for public fields.';
$MOD_LISTINGS['FILTER']               = 'Search and filter';
$MOD_LISTINGS['FILTER_FORM_ON']       = 'Show search and filter above the list';
$MOD_LISTINGS['HELP_FILTER_FORM']     = 'Search looks in the title and in public text fields. Filters: category, tag and fields marked as filter.';
$MOD_LISTINGS['SEARCH']               = 'Search';
$MOD_LISTINGS['FILTER_APPLY']         = 'Show';
$MOD_LISTINGS['FILTER_RESET']         = 'Show all';
$MOD_LISTINGS['FOUND']                = 'Entries found';
$MOD_LISTINGS['NO_MATCHES']           = 'No entries match this choice.';
$MOD_LISTINGS['RSS_ON']               = 'Offer a feed of the newest entries';
$MOD_LISTINGS['HELP_RSS']             = 'The 20 newest visible entries with their public fields. Address:';
$MOD_LISTINGS['ERR_CATEGORY_NAME']    = 'Please enter a name.';
$MOD_LISTINGS['ERR_CATEGORY_PARENT']  = 'A category cannot be placed below itself or one of its subcategories.';
$MOD_LISTINGS['ERR_TAG_NAME']         = 'Please enter a name for the tag.';
$MOD_LISTINGS['ERR_TAG_TAKEN']        = 'There is already a tag "%s".';
$MOD_LISTINGS['SAVED_CATEGORY']       = 'Category "%s" saved.';
$MOD_LISTINGS['DELETED_CATEGORY']     = 'Category "%s" deleted.';
$MOD_LISTINGS['SAVED_TAG']            = 'Tag "%s" saved.';
$MOD_LISTINGS['DELETED_TAG']          = 'Tag "%s" deleted.';

// 0.5: import from wbProfiles
$MOD_LISTINGS['IMPORT_TITLE']          = 'Import from wbProfiles';
$MOD_LISTINGS['IMPORT_TEASER']         = 'This website has data of wbProfiles. A directory can be taken over into this section once.';
$MOD_LISTINGS['IMPORT_OPEN']           = 'To the import';
$MOD_LISTINGS['IMPORT_INTRO']          = 'Choose a wbProfiles directory and look at the dry run first: it shows what is taken over and what is not, without changing anything. The wbProfiles data stays as it is.';
$MOD_LISTINGS['IMPORT_SOURCE_SECTION'] = 'wbProfiles directory';
$MOD_LISTINGS['IMPORT_SOURCE_OPTION']  = 'Section %1$s – %2$s (%3$s entries)';
$MOD_LISTINGS['IMPORT_NO_PAGE']        = 'section no longer exists';
$MOD_LISTINGS['IMPORT_NO_SOURCES']     = 'No wbProfiles entries found.';
$MOD_LISTINGS['IMPORT_FIX_ENCODING']   = 'Repair umlauts encoded twice ("SÃ¼ddorf" → "Süddorf")';
$MOD_LISTINGS['IMPORT_PUBLIC_EMAIL']   = 'Show the e-mail address publicly where wbProfiles linked it (field "E-Mail")';
$MOD_LISTINGS['IMPORT_DRY_RUN']        = 'Dry run';
$MOD_LISTINGS['IMPORT_PLAN']           = 'What the import creates';
$MOD_LISTINGS['IMPORT_RUN']            = 'Import now';
$MOD_LISTINGS['CONFIRM_IMPORT']        = 'Import %s entries into this section?';
$MOD_LISTINGS['IMPORT_DONE']           = 'Imported: %s entries.';
$MOD_LISTINGS['IMPORT_AFTER']          = 'Check fields, layouts and settings. Entries by visitors are switched off; switch them on in the settings if wanted. The wbProfiles section can be removed afterwards.';
$MOD_LISTINGS['IMPORT_PENDING']        = '%s wait for approval (locked or not confirmed in wbProfiles).';
$MOD_LISTINGS['IMPORT_EXPIRED']        = '%s are expired and hidden.';
$MOD_LISTINGS['IMPORT_FROM_FORMS']     = 'from the form definition of the template';
$MOD_LISTINGS['IMPORT_FROM_DATA']      = 'from the stored values (wbProfiles is not installed; labels made from the names)';
$MOD_LISTINGS['IMPORT_VALUES']         = 'Values';
$MOD_LISTINGS['IMPORT_SOURCE']         = 'wbProfiles';
$MOD_LISTINGS['IMPORT_NOTES']          = 'Changed on the way';
$MOD_LISTINGS['IMPORT_SKIPPED']        = 'Not taken over';
$MOD_LISTINGS['IMPORT_REPAIRED']       = '%s texts with umlauts encoded twice repaired.';
$MOD_LISTINGS['IMPORT_BROKEN']         = '%s texts look like umlauts encoded twice; the option above repairs them.';
$MOD_LISTINGS['IMPORT_HTML']           = '%s texts contained HTML; the text is kept, the markup removed.';
$MOD_LISTINGS['IMPORT_MISSING_CATEGORIES'] = 'Entries point to categories that no longer exist (ID %s); these assignments are dropped.';
$MOD_LISTINGS['IMPORT_BAD_EMAIL']      = '"%s" has no valid e-mail address; the entry has no owner.';
$MOD_LISTINGS['IMPORT_SKIP_PASSWORDS'] = '%s passwords: owners ask for an edit link by e-mail or log in with an account.';
$MOD_LISTINGS['IMPORT_FIELD_PICTURES'] = 'Pictures';
$MOD_LISTINGS['IMPORT_FIELD_FILES']    = 'Downloads';
$MOD_LISTINGS['IMPORT_FILES']          = 'Images and files';
$MOD_LISTINGS['IMPORT_FILES_MISSING']  = '%s images and files: not found in %s.';
$MOD_LISTINGS['IMPORT_SKIP_HEADER']    = 'Header pictures of entries.';
$MOD_LISTINGS['IMPORT_SKIP_TEMPLATES'] = 'The templates (.tpl): the list and detail layouts start with the defaults of Listings.';
$MOD_LISTINGS['ERR_IMPORT_NOT_EMPTY']  = 'The import only goes into an empty section: without fields, entries and categories.';
$MOD_LISTINGS['ERR_IMPORT_NO_SOURCE']  = 'This wbProfiles directory has no entries.';
$MOD_LISTINGS['ERR_IMPORT_FAILED']     = 'The import failed and was undone completely: %s';

// messages
$MOD_LISTINGS['SAVED_ITEM']     = 'Entry "%s" saved.';
$MOD_LISTINGS['DELETED_ITEM']   = 'Entry "%s" deleted.';
$MOD_LISTINGS['SAVED_FIELD']    = 'Field "%s" saved.';
$MOD_LISTINGS['DELETED_FIELD']  = 'Field "%s" deleted.';
$MOD_LISTINGS['SAVED_LAYOUTS']  = 'Layouts saved.';
$MOD_LISTINGS['SAVED_SETTINGS'] = 'Settings saved.';

// errors
$MOD_LISTINGS['ERR_REQUIRED']        = '"%s" is required.';
$MOD_LISTINGS['ERR_TOO_LONG']        = '"%s" may have at most %s characters.';
$MOD_LISTINGS['ERR_NUMBER']          = '"%s" must be a number.';
$MOD_LISTINGS['ERR_MIN']             = '"%s" must be at least %s.';
$MOD_LISTINGS['ERR_MAX']             = '"%s" must be at most %s.';
$MOD_LISTINGS['ERR_EMAIL']           = '"%s" is not a valid e-mail address.';
$MOD_LISTINGS['ERR_URL']             = '"%s" must be a web address starting with http:// or https://.';
$MOD_LISTINGS['ERR_PHONE']           = '"%s" is not a valid phone number.';
$MOD_LISTINGS['ERR_DATE']            = '"%s" is not a valid date.';
$MOD_LISTINGS['ERR_CHOICE']          = '"%s" contains a value that is not one of the choices.';
$MOD_LISTINGS['ERR_TITLE']           = 'Please enter a title.';
$MOD_LISTINGS['ERR_FIELD_TYPE']      = 'Unknown field type.';
$MOD_LISTINGS['ERR_FIELD_LABEL']     = 'Please enter a label.';
$MOD_LISTINGS['ERR_FIELD_NAME']      = 'The name may contain a–z, 0–9 and _ (at most 40), starting with a letter.';
$MOD_LISTINGS['ERR_FIELD_RESERVED']  = 'The name "%s" is used by the module itself.';
$MOD_LISTINGS['ERR_FIELD_TAKEN']     = 'This directory already has a field named "%s".';
$MOD_LISTINGS['ERR_FIELD_CHOICES']   = 'Please enter at least one choice.';
$MOD_LISTINGS['ERR_FIELD_MAXLENGTH'] = 'The maximum length must be a number from 1 to 65535.';
$MOD_LISTINGS['ERR_FIELD_RANGE']     = 'Minimum and maximum must be numbers, the minimum not above the maximum.';
$MOD_LISTINGS['ERR_PER_PAGE']        = 'Entries per page must be a number from 1 to %s.';
$MOD_LISTINGS['ERR_NOT_FOUND']       = 'The entry does not exist (any more).';
$MOD_LISTINGS['ERR_EXPIRED_FORM']    = 'The form has expired or was already sent. Please try again.';
$MOD_LISTINGS['ERR_DATABASE']        = 'The data could not be saved (database error).';

// 0.6: images and files
$MOD_LISTINGS['TYPE_IMAGE']          = 'Image';
$MOD_LISTINGS['TYPE_GALLERY']        = 'Several images';
$MOD_LISTINGS['TYPE_FILE']           = 'Files to download';
$MOD_LISTINGS['FIELD_MAX_FILES']     = 'Maximum number';
$MOD_LISTINGS['FIELD_MAX_MB']        = 'Maximum size per file (MB)';
$MOD_LISTINGS['FIELD_MAX_PX']        = 'Maximum edge (pixels)';
$MOD_LISTINGS['FIELD_EXTENSIONS']    = 'Allowed extensions';
$MOD_LISTINGS['HELP_MAX_MB']         = 'The server accepts at most %s per file.';
$MOD_LISTINGS['HELP_MAX_PX']         = 'Larger images are scaled down. Every image is saved again, which also removes camera and location data (EXIF).';
$MOD_LISTINGS['HELP_EXTENSIONS']     = 'Separated by commas. Scripts, HTML and SVG are never accepted.';
$MOD_LISTINGS['ERR_FIELD_LIMIT']     = '%s must be a number from %s to %s.';
$MOD_LISTINGS['ERR_FIELD_EXTENSIONS'] = 'Please enter at least one allowed extension, such as pdf.';
$MOD_LISTINGS['FILE_REMOVE']         = 'remove';
$MOD_LISTINGS['HELP_IMAGE_REPLACE']  = 'A new image replaces the current one.';
$MOD_LISTINGS['HELP_IMAGE_LIMITS']   = 'Up to %s images, each at most %s (%s).';
$MOD_LISTINGS['HELP_ONE_IMAGE']      = 'At most %2$s (%3$s).';
$MOD_LISTINGS['HELP_FILE_LIMITS']    = 'Up to %s files, each at most %s (%s).';
$MOD_LISTINGS['ERR_FILE_COUNT']      = '%s: at most %s files.';
$MOD_LISTINGS['ERR_FILE_SIZE']       = '%s is larger than %s.';
$MOD_LISTINGS['ERR_FILE_TYPE']       = '%s: this type of file is not accepted (allowed: %s).';
$MOD_LISTINGS['ERR_IMAGE_PIXELS']    = '%s has too many pixels. Please make the image smaller first.';
$MOD_LISTINGS['ERR_UPLOAD']          = '%s could not be uploaded. Please try again.';
$MOD_LISTINGS['ERR_UPLOAD_DIR']      = 'The folder for uploads cannot be written. Please tell the website admin.';
$MOD_LISTINGS['ERR_NO_GD']           = 'The server cannot process images (PHP extension GD missing).';
$MOD_LISTINGS['ERR_POST_SIZE']       = 'The files together are too large: at most %s per form. Nothing was saved.';

// 1.0: presets
$MOD_LISTINGS['PRESETS_TITLE']       = 'Presets';
$MOD_LISTINGS['PRESETS_INTRO']       = 'A preset brings a field set with settings, categories and layouts, without entries. It is loaded into an empty directory; everything can be changed afterwards.';
$MOD_LISTINGS['PRESETS_BUILT_IN']    = 'Included presets';
$MOD_LISTINGS['PRESETS_NONE']        = 'No presets found in the folder presets/.';
$MOD_LISTINGS['PRESET_COUNTS']       = '%s fields, %s categories';
$MOD_LISTINGS['PRESET_APPLY']        = 'Load';
$MOD_LISTINGS['PRESET_START']        = 'Start with a preset';
$MOD_LISTINGS['CONFIRM_PRESET']      = 'Load the preset "%s" into this directory?';
$MOD_LISTINGS['PRESET_UPLOAD']       = 'Load a preset file';
$MOD_LISTINGS['PRESET_FILE']         = 'File (.json)';
$MOD_LISTINGS['HELP_PRESET_FILE']    = 'A file exported from another Listings directory, at most 1 MB.';
$MOD_LISTINGS['PRESET_EXPORT']       = 'Export this directory as preset';
$MOD_LISTINGS['HELP_PRESET_EXPORT']  = 'The file holds fields, settings, categories, the tags of the list and edited layouts. Entries, images and the notification address stay here.';
$MOD_LISTINGS['PRESET_EXPORT_EMPTY'] = 'There is nothing to export yet: the directory has no fields.';
$MOD_LISTINGS['PRESET_NAME']         = 'Name of the preset';
$MOD_LISTINGS['PRESET_DOWNLOAD']     = 'Download';
$MOD_LISTINGS['MSG_PRESET_APPLIED']  = 'Preset "%s" loaded. Please check fields, settings and layouts.';
$MOD_LISTINGS['ERR_PRESET_NOT_EMPTY'] = 'A preset only goes into an empty directory: without fields, entries and categories.';
$MOD_LISTINGS['ERR_PRESET_NO_FILE']  = 'Please choose a preset file.';
$MOD_LISTINGS['ERR_PRESET_SIZE']     = 'The preset file is larger than 1 MB.';
$MOD_LISTINGS['ERR_PRESET_JSON']     = 'The file is not valid JSON.';
$MOD_LISTINGS['ERR_PRESET_FORMAT']   = 'The file is not a Listings preset.';
$MOD_LISTINGS['ERR_PRESET_VERSION']  = 'The preset was made by a newer version of Listings.';
$MOD_LISTINGS['ERR_PRESET_TOO_MANY'] = 'A preset may have at most %s fields.';
$MOD_LISTINGS['ERR_PRESET_TOO_MANY_CATS'] = 'A preset may have at most %s categories.';
$MOD_LISTINGS['ERR_PRESET_FAILED']   = 'Loading the preset failed and was undone: %s';
