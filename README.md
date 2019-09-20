# ghost-acf-form
WordPress plugin to render one or more ACF forms on the frontend of a website using shortcodes, record entries in the backend and send out email notifications.

### Requirements ###

1. WordPress

2. Advanced Custom Fields (tested with the Pro version) https://advancedcustomfields.com

### Setup of plugin and form ###

1. Install the plugin and activate

2. Create an ACF form which must have at least the following fields..

    1. First Name (first_name) [text]
    2. Last Name (last_name) [text]
    3. Email (email) [email]  
    
4. Assign the form to the Form Entry content type

The form may have any other fields within it. These will all be displayed on the backend. The First Name, Last Name and Email fields are used for the description of entries and within the email notifications to admin. The email contains a link to the backend to view the full entry.

Optionally, if the form has a textarea field named 'message', this field will be sent through in the email. This is designed to be used with contact forms.

Multiple forms may be used on different posts/pages of the website. Note: they must have a unique title.

### Displaying forms on the frontend ###

The form(s) are displayed on the frontend using a shortcode with the title of the form specified in the shortcode.

Example shortcode:

`[ghost_acf_form form_name='FORM NAME']`

Other parameters:

`email_to='email@address.com'`

If the email_to parameter is used, this will send notifications to that email address instead of the site admin email address.

`email_off='true'`

To disable emails completely, the email_off paramater can be used.
