# Database Cleanup Scripts

This directory contains scripts for cleaning up old or unwanted reservations from the `class_reservations` table.

## Available Cleanup Methods

### 1. Web Interface (Admin Panel)
- **Location**: `admin/cleanup-reservations.php`
- **Access**: Admin users only
- **Features**: 
  - User-friendly web interface
  - Real-time database statistics
  - Multiple cleanup options
  - Confirmation dialogs
  - Success/error messages

### 2. Command Line Script
- **Location**: `scripts/cleanup-reservations.php`
- **Access**: Server command line
- **Features**:
  - Batch processing
  - Dry-run mode
  - Detailed logging
  - Multiple cleanup options

## Cleanup Options

### By Date
1. **Before Date**: Delete all reservations before a specific date
2. **After Date**: Delete all reservations after a specific date
3. **On Date**: Delete all reservations on a specific date
4. **Date Range**: Delete all reservations within a date range
5. **Older Than Days**: Delete reservations older than X days

### By Status
- **Pending**: Delete pending reservations
- **Approved**: Delete approved reservations
- **Rejected**: Delete rejected reservations
- **Status + Date**: Delete reservations with specific status before a date

## Usage Examples

### Web Interface
1. Login as admin user
2. Go to Admin Panel
3. Click "Database Cleanup" button
4. Select cleanup type and parameters
5. Confirm deletion

### Command Line

```bash
# Delete reservations older than 30 days
php scripts/cleanup-reservations.php --older-than-days=30

# Delete all rejected reservations
php scripts/cleanup-reservations.php --status=rejected

# Delete reservations before 2024-01-01
php scripts/cleanup-reservations.php --before-date=2024-01-01

# Delete reservations in date range
php scripts/cleanup-reservations.php --date-range=2024-01-01,2024-12-31

# Dry run (preview without deleting)
php scripts/cleanup-reservations.php --older-than-days=30 --dry-run

# Show help
php scripts/cleanup-reservations.php --help
```

## Safety Features

### Confirmation
- Web interface requires confirmation dialog
- Command line shows count before deletion
- Dry-run mode available for testing

### Backup Recommendations
- Always backup database before cleanup
- Test with dry-run mode first
- Start with small batches

### Access Control
- Web interface: Admin users only
- Command line: Server access required
- Database credentials required

## Database Structure

The cleanup operations work on the `class_reservations` table:

```sql
CREATE TABLE `class_reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reservation_id`),
  KEY `idx_date` (`date`),
  KEY `idx_class_date` (`class_id`,`date`)
);
```

## Common Cleanup Scenarios

### 1. Regular Maintenance
```bash
# Delete rejected reservations older than 7 days
php scripts/cleanup-reservations.php --status=rejected --status-before-date=$(date -d '7 days ago' +%Y-%m-%d)

# Delete all reservations older than 1 year
php scripts/cleanup-reservations.php --older-than-days=365
```

### 2. Data Migration
```bash
# Delete all reservations before a specific date
php scripts/cleanup-reservations.php --before-date=2024-01-01
```

### 3. Testing Cleanup
```bash
# Remove test data from specific date range
php scripts/cleanup-reservations.php --date-range=2024-01-01,2024-01-31
```

## Monitoring and Logging

### Statistics Display
Both interfaces show:
- Total reservations count
- Count by status (pending, approved, rejected)
- Number of records affected

### Error Handling
- Database connection errors
- Invalid date formats
- Permission errors
- SQL execution errors

## Troubleshooting

### Common Issues
1. **Permission Denied**: Ensure user has admin role
2. **Database Connection**: Check database credentials
3. **Invalid Date**: Use YYYY-MM-DD format
4. **No Records Found**: Verify date criteria

### Recovery
- Restore from database backup
- Check database logs
- Contact system administrator

## Security Considerations

- Only admin users can access cleanup functions
- All operations are logged
- Confirmation required for destructive operations
- Database credentials stored securely
- Input validation prevents SQL injection

## Performance Notes

- Large deletions may take time
- Consider running during low-traffic periods
- Use date indexes for better performance
- Monitor database locks during operation
