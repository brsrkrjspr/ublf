# How to Set Up OpenAI Credential in n8n

## Problem
The managed OpenAI credential provided by n8n cannot be edited. You need to create your own credential.

## Solution: Create a New OpenAI Credential

### Step 1: Get Your OpenAI API Key

1. Go to https://platform.openai.com/api-keys
2. Sign in to your OpenAI account (or create one if needed)
3. Click **"Create new secret key"**
4. Give it a name (e.g., "UB Lost & Found Chatbot")
5. **Copy the API key immediately** - you won't be able to see it again!

### Step 2: Create Credential in n8n

1. **In n8n**, go to your workflow: "UB Lost & Found Chatbot"
2. Click on the **"Generate AI Response"** node
3. In the node settings, find **"Credential to connect with"** dropdown
4. Click the dropdown - you'll see the managed credential (can't edit)
5. **Click "Create New Credential"** (or the "+" button)
6. A modal will appear - select **"OpenAI"**
7. **Enter your API key** in the "API Key" field
8. Optionally add a name like "My OpenAI API Key"
9. Click **"Save"** or **"Create"**

### Step 3: Select Your New Credential

1. After creating, the credential should be automatically selected
2. If not, click the **"Credential to connect with"** dropdown again
3. Select your newly created credential (not the managed one)
4. The node should now show your credential name

### Step 4: Configure the Node (if needed)

Make sure the "Generate AI Response" node has:
- **Resource**: `Chat` (or `Text`)
- **Operation**: `Message a Model` (or `Message`)
- **Model**: `gpt-3.5-turbo` or `gpt-4`
- **System Message**: Should be pre-filled from the workflow
- **User Message**: Should reference the input from previous nodes

### Step 5: Test the Workflow

1. **Activate the workflow** (toggle ON)
2. Click on **"Webhook Trigger"** node
3. Click **"Test step"** button
4. Send a test message:
   ```json
   {
     "message": "hello",
     "studentNo": "TEST001",
     "studentName": "Test User",
     "studentEmail": "test@ub.edu.ph"
   }
   ```
5. Check if the workflow executes successfully
6. The "Generate AI Response" node should now work

## Troubleshooting

### "Invalid API key" error
- Double-check you copied the full API key
- Make sure there are no extra spaces
- Verify the key is active in OpenAI dashboard

### "Credential not found" error
- Make sure you selected your new credential (not the managed one)
- Try recreating the credential

### Workflow still fails
- Check n8n execution logs to see which node failed
- Verify the API key has credits/usage available
- Make sure the workflow is activated

## Cost Note

OpenAI API usage is pay-as-you-go:
- **GPT-3.5-turbo**: ~$0.0015 per 1K tokens (very cheap)
- **GPT-4**: More expensive but better quality
- For a chatbot, GPT-3.5-turbo is usually sufficient

You can set usage limits in your OpenAI account to control costs.

## Alternative: Use n8n's Free Credits (Limited)

If you want to use n8n's managed credential with free credits:
- It has limited usage
- You cannot edit it
- It may not work for production use
- **Recommendation**: Create your own credential for reliability

---

**Once you've created your own credential and selected it in the node, the chatbot should work!**

