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
$MOD_LISTINGS['WAITING_COUNT'] = '%d waiting for approval';
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
$MOD_LISTINGS['MAIL_EDIT_TEXT']       = "You asked for a link to change your entry in \"%s\". Each link is valid for %d minutes:";
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
$MOD_LISTINGS['ERR_RUNTIME']        = 'The runtime must be a number from 0 to %d.';
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

// messages
$MOD_LISTINGS['SAVED_ITEM']     = 'Entry "%s" saved.';
$MOD_LISTINGS['DELETED_ITEM']   = 'Entry "%s" deleted.';
$MOD_LISTINGS['SAVED_FIELD']    = 'Field "%s" saved.';
$MOD_LISTINGS['DELETED_FIELD']  = 'Field "%s" deleted.';
$MOD_LISTINGS['SAVED_LAYOUTS']  = 'Layouts saved.';
$MOD_LISTINGS['SAVED_SETTINGS'] = 'Settings saved.';

// errors
$MOD_LISTINGS['ERR_REQUIRED']        = '"%s" is required.';
$MOD_LISTINGS['ERR_TOO_LONG']        = '"%s" may have at most %d characters.';
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
$MOD_LISTINGS['ERR_PER_PAGE']        = 'Entries per page must be a number from 1 to %d.';
$MOD_LISTINGS['ERR_NOT_FOUND']       = 'The entry does not exist (any more).';
$MOD_LISTINGS['ERR_EXPIRED_FORM']    = 'The form has expired or was already sent. Please try again.';
$MOD_LISTINGS['ERR_DATABASE']        = 'The data could not be saved (database error).';
