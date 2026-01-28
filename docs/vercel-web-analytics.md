# Getting started with Vercel Web Analytics

This guide will help you get started with using Vercel Web Analytics on your project, showing you how to enable it, view your data in the dashboard, and understand how it's implemented in this PHP-based Academic Data Repository.

## What is Vercel Web Analytics?

Vercel Web Analytics provides privacy-friendly, performance-focused analytics for your web applications. It tracks visitors and page views without compromising user privacy or site performance.

## Prerequisites

- A Vercel account. If you don't have one, you can [sign up for free](https://vercel.com/signup).
- A Vercel project. If you don't have one, you can [create a new project](https://vercel.com/new).
- The project must be deployed to Vercel.

## Enable Web Analytics in Vercel

1. Go to the [Vercel dashboard](https://vercel.com/dashboard)
2. Select your Project
3. Click the **Analytics** tab
4. Click **Enable** from the dialog

> **💡 Note:** Enabling Web Analytics will add new routes (scoped at `/_vercel/insights/*`) after your next deployment.

## Implementation in This Project

This Academic Data Repository is a PHP-based application. The Vercel Web Analytics tracking script has been implemented across all pages using the HTML/JavaScript approach.

### What Was Added

The following analytics script has been added to all page templates:

```html
<!-- Vercel Web Analytics -->
<script>
  window.va = window.va || function () { (window.vaq = window.vaq || []).push(arguments); };
</script>
<script defer src="/_vercel/insights/script.js"></script>
```

### Files Modified

The analytics script has been integrated into:

1. **includes/footer.php** - Used by most pages (browse, datasets, login, register, dashboard, etc.)
2. **index.php** - Home page
3. **admin_dashboard.php** - Admin dashboard
4. **user_dashboard.php** - User dashboard
5. **install.php** - Installation page
6. **project.php** - Individual project pages

This ensures comprehensive tracking across all pages of the application.

## Deploy Your Changes

After enabling analytics in the Vercel dashboard, deploy your app using one of these methods:

### Using Vercel CLI

```bash
vercel deploy
```

### Using Git Integration (Recommended)

If you haven't already, [connect your project's Git repository](https://vercel.com/docs/git), which will enable Vercel to automatically deploy your latest commits to main without terminal commands.

Once your app is deployed, it will start tracking visitors and page views.

> **💡 Note:** If everything is set up properly, you should be able to see a Fetch/XHR request in your browser's Network tab from `/_vercel/insights/view` when you visit any page.

## View Your Data in the Dashboard

Once your app is deployed and users have visited your site, you can view your data in the dashboard.

### Steps to View Analytics

1. Go to your [Vercel dashboard](https://vercel.com/dashboard)
2. Select your project
3. Click the **Analytics** tab

After a few days of visitors, you'll be able to start exploring your data by viewing and filtering the panels.

### Available Metrics

- **Page Views**: Total number of page views
- **Visitors**: Unique visitors to your site
- **Top Pages**: Most visited pages
- **Referrers**: Where your traffic comes from
- **Devices**: Desktop vs mobile breakdown
- **Locations**: Geographic distribution of visitors

## Privacy and Compliance

Vercel Web Analytics is designed with privacy in mind:

- **No cookies**: Analytics work without cookies
- **No personal data**: No personally identifiable information is collected
- **GDPR compliant**: Meets European privacy standards
- **CCPA compliant**: Meets California privacy standards

Learn more about how Vercel supports [privacy and data compliance standards](https://vercel.com/docs/analytics/privacy-policy) with Vercel Web Analytics.

## Advanced Features

### Custom Events (Pro and Enterprise Plans)

Users on Pro and Enterprise plans can add custom events to track specific user interactions such as:

- Button clicks
- Form submissions
- Dataset downloads
- User registrations
- Project creations

To implement custom events, you can use the following JavaScript:

```javascript
window.va('event', {
  name: 'dataset_download',
  data: {
    dataset_id: '123',
    dataset_name: 'Sample Dataset'
  }
});
```

### Filtering Data

The Analytics dashboard allows you to filter data by:

- Date range
- Page paths
- Referrers
- Countries
- Devices

## Troubleshooting

### Analytics Not Showing Up

If you don't see analytics data:

1. **Wait 24-48 hours**: Initial data takes time to appear
2. **Check deployment**: Ensure the latest code is deployed
3. **Verify network requests**: Look for `/_vercel/insights/view` in browser DevTools
4. **Check dashboard**: Ensure analytics is enabled in project settings

### Script Not Loading

If the analytics script isn't loading:

1. **Check Vercel configuration**: Ensure analytics is enabled
2. **Verify routes**: The `/_vercel/insights/*` routes should be available
3. **Check browser console**: Look for any error messages
4. **Test in incognito**: Rule out browser extensions blocking scripts

## Next Steps

Now that you have Vercel Web Analytics set up, you can explore the following topics to learn more:

- [Learn about filtering data](https://vercel.com/docs/analytics/filtering)
- [Read about privacy and compliance](https://vercel.com/docs/analytics/privacy-policy)
- [Explore pricing](https://vercel.com/docs/analytics/limits-and-pricing)
- [Learn how to set up custom events](https://vercel.com/docs/analytics/custom-events)
- [Troubleshooting guide](https://vercel.com/docs/analytics/troubleshooting)

## Support

For additional help:

- [Vercel Documentation](https://vercel.com/docs)
- [Vercel Community](https://vercel.com/community)
- [Contact Vercel Support](https://vercel.com/support)

## Project-Specific Notes

This Academic Data Repository is specifically designed for educational institutions. Analytics can help you understand:

- Which datasets are most popular
- User engagement patterns
- Peak usage times
- Geographic distribution of users
- Most accessed features

This data can inform decisions about:

- Dataset curation priorities
- Feature development
- Server capacity planning
- User support needs
