$output = php vendor/bin/phpunit --no-coverage 2>&1
$output | Out-File -FilePath "test_output_full.txt" -Encoding UTF8
Write-Output $output
