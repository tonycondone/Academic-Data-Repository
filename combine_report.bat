@echo off
echo ========================================
echo COMBINING YOUR DataShare Platform REPORT
echo ========================================
echo.

echo Creating complete report file...
echo.

REM Combine all markdown files into one
type "Final_Year_Project_Report_Complete.md" > "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
echo ============================================ >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
type "Final_Year_Project_Report_Complete_Part2.md" >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
echo ============================================ >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
type "Final_Year_Project_Report_Complete_Part3.md" >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
echo ============================================ >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
type "Final_Year_Project_Report_Complete_Part4.md" >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
echo ============================================ >> "COMPLETE_FINAL_REPORT.txt"
echo. >> "COMPLETE_FINAL_REPORT.txt"
type "Final_Year_Project_Report_Complete_Part5.md" >> "COMPLETE_FINAL_REPORT.txt"

echo.
echo ✓ Complete report created: COMPLETE_FINAL_REPORT.txt
echo.
echo ========================================
echo NEXT STEPS:
echo ========================================
echo 1. Open COMPLETE_FINAL_REPORT.txt
echo 2. Select all (Ctrl+A) and Copy (Ctrl+C)
echo 3. Open Microsoft Word
echo 4. Paste (Ctrl+V)
echo 5. Apply formatting as per TTU guidelines
echo 6. Add your screenshots
echo 7. Save as PDF
echo.
echo Press any key to open the combined report...
pause > nul

REM Try to open the file
start notepad "COMPLETE_FINAL_REPORT.txt"
