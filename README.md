# WordPress Auth using Google Authenticator

WordPress Auth using Google Authenticator is a simple plugin which allows authorization with Google Authenticator tokens.

### Description


WordPress Auth using Google Authenticator is a simple plugin which allows authorization with Google Authenticator tokens. Very easy to install and configure. 

### Installation

- Login to WordPress admin panel, go to 'Plugins', next click 'Add New', choose 'Upload', click 'Browse' and find a xlt-totp-auth.zip and next click 'Upload Now'
- If plugin is installed, remember to activate it.
- Go to `Settings > Google Token Authentication` and check if `Token authentication enabled` is checked.
- Go to `Users > Your profile` and scroll to the bottom of page and find `Google Authenticator` section.
- Check `Token authentication enabled`. `Secret code` will appear. Click `Generate new` and wait for new secret code. After generation click `Update profile`.
- Scan QR Code in your Google Authenticator application or enter `Secret code for Google Authenticator` code manually. You should also write this code in safe place. If you reinstall Google Authenticator software or loose your phone you will not be able to login.
- Next time on login screen `Google Authenticator token` field will appear. Enter your login, password and generated code and login. If user has not enabled `token authentication` just leave this field empty.

### Frequently Asked Questions


**Q**: _What should I do if I lost my phone or reinstall Google Authenticator?_

**A**: Probably the best way is to delete plugin folder using FTP or any file manager.
