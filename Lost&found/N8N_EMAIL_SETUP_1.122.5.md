# Email Node Setup for n8n 1.122.5

## Overview
The approval notifications workflow uses email nodes that need to be configured after importing the workflow.

## Email Authentication Options

### Option 1: Gmail oAuth2 (Recommended for Gmail)
1. In n8n, go to **Credentials** → **Add Credential**
2. Search for **"Gmail OAuth2"** or **"Email Send"**
3. Select **Gmail OAuth2 API**
4. Follow the setup wizard:
   - Click "Connect my account"
   - Authorize n8n to access your Gmail account
   - Complete the OAuth flow
5. In the workflow, open **"Send Email to Student"** node
6. Under **Authentication**, select your Gmail OAuth2 credential
7. Repeat for **"Send Email to Admin"** node

### Option 2: SMTP (Universal - Works with any email provider)
1. In n8n, go to **Credentials** → **Add Credential**
2. Search for **"SMTP"**
3. Fill in the SMTP settings:
   - **Host**: `smtp.gmail.com` (for Gmail) or your SMTP server
   - **Port**: `587` (TLS) or `465` (SSL)
   - **User**: Your email address (`foundlost004@gmail.com`)
   - **Password**: Your email password or App Password (for Gmail)
   - **Secure**: `TLS` or `SSL` depending on port
4. In the workflow, open **"Send Email to Student"** node
5. Change **Authentication** from `oAuth2` to `smtp`
6. Select your SMTP credential
7. Repeat for **"Send Email to Admin"** node

### Option 3: SendGrid (Alternative email service)
1. Sign up for SendGrid account
2. Create an API key in SendGrid dashboard
3. In n8n, add **SendGrid** credential
4. Use SendGrid API key in email nodes

## Gmail App Password (If using SMTP with Gmail)

If you're using Gmail with SMTP and have 2FA enabled:
1. Go to Google Account → Security
2. Enable **2-Step Verification** (if not already enabled)
3. Go to **App passwords**
4. Generate a new app password for "Mail"
5. Use this app password (not your regular password) in SMTP settings

## Testing

After configuration:
1. Activate the workflow
2. Test by approving/rejecting a report in the admin dashboard
3. Check that emails are sent to both student and admin
4. Check n8n execution logs if emails fail

## Troubleshooting

### "Authentication failed"
- Verify credentials are correct
- For Gmail oAuth2: Re-authorize the connection
- For SMTP: Check host, port, and password

### "Email not sending"
- Check n8n execution logs for detailed errors
- Verify email addresses are valid
- Check spam folders

### "Node not found" errors
- Ensure workflow is imported correctly
- Check node connections in the workflow editor

## Notes for n8n 1.122.5

- Email node `typeVersion: 2.1` is compatible with this version
- Both oAuth2 and SMTP authentication methods are supported
- If oAuth2 doesn't work, switch to SMTP for better compatibility

