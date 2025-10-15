# Real-Time Order Management Dashboard

## Overview
The Order Management Dashboard provides real-time statistics and analytics for monitoring orders. The dashboard automatically refreshes every 15 seconds to display the latest data.

## Features
- Real-time order statistics
- Dynamic charts and graphs
- Automatic data refresh
- Responsive design
- Export capabilities

## Dashboard Components

### 1. Statistics Cards
- **Total Orders**: Overall order count
- **Today's Orders**: Orders placed today
- **Pending Orders**: Orders awaiting processing
- **Processing**: Orders currently being processed
- **Delivered Orders**: Successfully completed orders
- **Cancelled Orders**: Cancelled order count

### 2. Charts
- **Order Status Distribution**: Pie chart showing order statuses
- **7-Day Orders Trend**: Line chart showing order trends and revenue

### 3. Payment Methods
- Shows distribution of payment methods used
- Displays order counts and revenue per payment method

## Real-Time Functionality

### Auto-Refresh
- Dashboard refreshes every 15 seconds
- Manual refresh available via the refresh button
- Visual indicators show when data is loading

### Data Updates
- Statistics update in real-time
- Charts dynamically update with new data
- Growth indicators show trends over time

## API Endpoints

### Dashboard Statistics
```
GET /admin/apis/order-dashboard-stats.php
```

Returns comprehensive order statistics including:
- Order counts by status
- Revenue data
- Growth metrics
- Payment method distribution
- Daily order trends

### Payment Methods Statistics
```
GET /admin/apis/payment-methods-stats.php
```

Returns payment method distribution and statistics.

## Customization

### Refresh Interval
The refresh interval can be adjusted by modifying the `DASHBOARD_REFRESH_INTERVAL` constant in the JavaScript code.

### Data Filters
The dashboard can be filtered by:
- Date range
- Order status
- Payment status
- Search terms

## Troubleshooting

### Dashboard Not Updating
1. Check browser console for JavaScript errors
2. Verify API endpoints are accessible
3. Ensure database connection is working
4. Check server logs for errors

### Charts Not Displaying
1. Verify Chart.js library is loaded
2. Check data format from API
3. Ensure canvas elements are properly initialized

### Performance Issues
1. Reduce refresh interval
2. Limit date range for historical data
3. Optimize database queries