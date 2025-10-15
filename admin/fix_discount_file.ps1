# PowerShell script to fix the discount management file
$inputFile = "discounts-management.php"
$outputFile = "discounts-management-fixed.php"

# Read the file
$content = Get-Content $inputFile

# Process each line
$fixedContent = foreach ($line in $content) {
    if ($line -match "Check if discount is currently active based on dates" -or
        $line -match '\$currentDate = date' -or
        $line -match "start_date <= \?" -or
        $line -match '\$params = array_merge.*currentDate') {
        "// $line"
    } else {
        $line
    }
}

# Write the fixed content
$fixedContent | Set-Content $outputFile

Write-Host "Fixed file created: $outputFile"
Write-Host "The problematic lines have been commented out."
Write-Host ""
Write-Host "To apply the fix:"
Write-Host "1. Backup your current file: copy discounts-management.php discounts-management-backup.php"
Write-Host "2. Replace with fixed version: copy discounts-management-fixed.php discounts-management.php"
Write-Host "3. Test the discount creation"
