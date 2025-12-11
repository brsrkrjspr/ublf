# Render Deployment Guide

## Quick Fix for "Couldn't find a package.json" Error

Render is trying to deploy this as a Node.js application, but this is a **PHP application**. 

## Solution

The `render.yaml` file has been created to configure Render to use Docker instead of Node.js.

### Option 1: Using render.yaml (Recommended)

1. **Make sure `render.yaml` is in your repository root**
2. **In Render Dashboard:**
   - Go to your service settings
   - Under "Build & Deploy", make sure it's set to use the `render.yaml` file
   - Or create a new service and Render will automatically detect `render.yaml`

### Option 2: Manual Configuration in Render Dashboard

If you're not using `render.yaml`, configure manually:

1. **Go to your service in Render Dashboard**
2. **Settings → Build & Deploy:**
   - **Environment**: Select **"Docker"** (not Node.js)
   - **Dockerfile Path**: `./Dockerfile`
   - **Docker Context**: `.` (root directory)
3. **Save changes**

### Option 3: Delete and Recreate Service

1. Delete the current service
2. Create a new **Web Service**
3. Connect your GitHub repository
4. Render should auto-detect the `Dockerfile` and `render.yaml`
5. Select **"Docker"** as the environment

## Environment Variables

Set these in Render Dashboard → Environment:

- `APACHE_DOCUMENT_ROOT`: `/var/www/html/Lost&found/htdocs/public` (already set in render.yaml)
- Database credentials (if using external database):
  - `DB_HOST`: Your database host
  - `DB_NAME`: Your database name
  - `DB_USER`: Your database username
  - `DB_PASS`: Your database password

## Database Setup

Render doesn't provide MySQL by default. You'll need to:

1. **Use Render PostgreSQL** (requires code changes to use PostgreSQL)
2. **Use external MySQL service** (like PlanetScale, AWS RDS, etc.)
3. **Use Render MySQL addon** (if available)

Update `Lost&found/htdocs/includes/Database.php` with your database credentials.

## After Deployment

1. Your app will be available at: `https://your-service-name.onrender.com`
2. The root path will serve files from `Lost&found/htdocs/public/`
3. Make sure the `assets/uploads/` folder has write permissions (handled in Dockerfile)

## Troubleshooting

### Still getting Node.js errors?
- Make sure `render.yaml` is committed to your repository
- Check that Render is using the `render.yaml` file (Settings → Build & Deploy)
- Try deleting and recreating the service

### Build fails?
- Check Dockerfile syntax
- Verify all paths are correct
- Check Render build logs for specific errors

### App not loading?
- Verify `APACHE_DOCUMENT_ROOT` environment variable is set correctly
- Check that files are in the correct directory structure
- Review Apache error logs in Render dashboard

## Files Created

- ✅ `render.yaml` - Render configuration file
- ✅ `.dockerignore` - Docker build optimization
- ✅ `Dockerfile` - Updated for better compatibility

---

**Your app should now deploy successfully on Render!** 🚀

