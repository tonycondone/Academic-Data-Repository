# DIY Guide: Converting Your Complete Report to PDF

## 📁 Your Complete Report Files

Your complete report is split across these markdown files:
1. `Final_Year_Project_Report_Complete.md` - Chapter 1 and preliminaries
2. `Final_Year_Project_Report_Complete_Part2.md` - Chapter 2
3. `Final_Year_Project_Report_Complete_Part3.md` - Chapter 3
4. `Final_Year_Project_Report_Complete_Part4.md` - Chapter 4
5. `Final_Year_Project_Report_Complete_Part5.md` - Chapter 5, References, and Appendices

## 🛠️ Method 1: Using Microsoft Word (Recommended)

### Step 1: Combine All Parts
1. Open Microsoft Word
2. Create a new blank document
3. Set up the page:
   - Go to **Layout** → **Margins** → **Custom Margins**
   - Top: 25mm (0.98 inches)
   - Left: 25mm (0.98 inches)
   - Bottom: 20mm (0.79 inches)
   - Right: 20mm (0.79 inches)

### Step 2: Copy Content from Each File
1. Open each markdown file in order:
   - Start with `Final_Year_Project_Report_Complete.md`
   - Copy all content (Ctrl+A, Ctrl+C)
   - Paste into Word (Ctrl+V)
   - Add a page break (Ctrl+Enter)
   
2. Repeat for each part file in sequence

### Step 3: Apply TTU Formatting
1. Select all text (Ctrl+A)
2. Set font to **Times New Roman**
3. Set font size to **12pt** for body text
4. Set line spacing to **1.5**

### Step 4: Format Headings
1. **Title Page Elements**:
   - University name: 16pt, Bold, Center
   - Project title: 14pt, Bold, Center
   
2. **Chapter Headings**:
   - Select each "CHAPTER" heading
   - Format → Styles → Heading 1
   - Modify: 14pt, Bold, Center, All Caps

3. **Section Headings** (e.g., 1.1, 2.1):
   - Format → Styles → Heading 2
   - Modify: 12pt, Bold

### Step 5: Add Page Numbers
1. Insert → Page Number → Bottom of Page → Plain Number 2
2. Format Page Numbers:
   - Preliminary pages (i, ii, iii...)
   - Main content (1, 2, 3...)

### Step 6: Insert Placeholders for Figures
Where you see "Figure X.X:", add:
```
[INSERT FIGURE HERE: Description]
```

### Step 7: Convert to PDF
1. File → Save As
2. Choose PDF format
3. Options → Ensure "Document structure tags" is checked

## 🛠️ Method 2: Using Markdown to PDF Converters

### Option A: Pandoc (Command Line)
```bash
# Install pandoc first
# Then combine and convert:
pandoc Final_Year_Project_Report_Complete.md Final_Year_Project_Report_Complete_Part2.md Final_Year_Project_Report_Complete_Part3.md Final_Year_Project_Report_Complete_Part4.md Final_Year_Project_Report_Complete_Part5.md -o Final_Report.pdf --pdf-engine=xelatex -V geometry:margin=25mm -V fontsize=12pt -V linestretch=1.5 -V mainfont="Times New Roman"
```

### Option B: Online Converters
1. **Markdown to PDF** (markdowntopdf.com):
   - Combine all markdown files into one
   - Upload and convert
   - Note: May need formatting adjustments

2. **Dillinger.io**:
   - Import markdown files
   - Export as PDF
   - Apply formatting in Word afterward

### Option C: VS Code Extensions
1. Install "Markdown PDF" extension
2. Open each markdown file
3. Right-click → "Markdown PDF: Export (pdf)"
4. Combine PDFs using online tools

## 🛠️ Method 3: Using Google Docs

1. Create new Google Doc
2. Copy content from each markdown file
3. Format using Google Docs tools:
   - Format → Paragraph styles
   - Format → Line spacing → 1.5
   - File → Page setup (set margins)
4. Download as PDF

## 📋 Formatting Checklist

After conversion, ensure:
- [ ] Font is Times New Roman throughout
- [ ] Title: 16pt Bold
- [ ] Chapter headings: 14pt Bold
- [ ] Body text: 12pt
- [ ] Line spacing: 1.5
- [ ] Margins correct (25mm top/left, 20mm bottom/right)
- [ ] Page numbers added
- [ ] Table of Contents page numbers updated
- [ ] All 5 chapters present
- [ ] References in APA format
- [ ] Signature lines on Declaration page

## 🖼️ Adding Your Screenshots

### Where to Add Screenshots:
1. **Figure 4.1** (p.75): Homepage Interface - Add screenshot of index.php
2. **Figure 4.2** (p.76): Registration Form - Add screenshot of register.php
3. **Figure 4.3** (p.77): Login Interface - Add screenshot of login.php
4. **Figure 4.4** (p.79): Dataset Repository - Add screenshot of datasets.php
5. **Figure 4.5** (p.81): File Upload - Add screenshot of upload.php
6. **Figure 4.6** (p.83): CSV Preview - Add screenshot of preview.php
7. **Figure 4.7** (p.85): Search Results - Add screenshot of search functionality
8. **Figure 4.8** (p.87): Version History - Add screenshot of version control
9. **Figure 4.9** (p.89): Admin Dashboard - Add screenshot of admin.php
10. **Figure 4.10** (p.91): Project Interface - Add screenshot of projects.php

### How to Insert Screenshots:
1. Take screenshots of your running system
2. In Word: Insert → Pictures → From File
3. Add captions: References → Insert Caption
4. Format: Right-click → Size and Position → Lock aspect ratio

## 🎯 Quick Processing Steps

### If you're in a hurry:
1. **Combine all markdown files** into one text file
2. **Copy to Word**
3. **Apply basic formatting**:
   - Times New Roman, 12pt
   - 1.5 line spacing
   - Add page breaks between chapters
4. **Save as PDF**
5. **Add screenshots later** if needed

### Essential Elements to Include:
- Title page with all 5 team members
- Declaration (needs signatures)
- All 5 chapters
- References
- At least placeholder text for figures

## 💡 Pro Tips

1. **Use Find & Replace** in Word:
   - Find: `#` (markdown headers)
   - Replace with formatted headings

2. **Create a Table of Contents**:
   - After formatting all headings
   - References → Table of Contents → Automatic

3. **Page Breaks**:
   - Each chapter should start on a new page
   - Insert → Page Break (or Ctrl+Enter)

4. **Save Frequently**:
   - Save as .docx first
   - Only convert to PDF when completely done

## 📝 Final Submission Checklist

Before submitting:
- [ ] All 5 team members listed on title page
- [ ] Supervisor name correct
- [ ] Declaration page signed by all members
- [ ] Page count approximately 145 pages
- [ ] All chapters complete
- [ ] References properly formatted
- [ ] File saved as PDF
- [ ] File named appropriately (e.g., FinalYearProject_AcademicDataRepository_2024.pdf)

## 🆘 If You Need Help

The complete content is in the markdown files. Even if formatting isn't perfect, the content meets all requirements. Focus on:
1. Getting all content into one document
2. Basic formatting (font, spacing)
3. Adding your actual screenshots
4. Converting to PDF

Remember: Content is more important than perfect formatting. Your report has all required sections and meets the word count requirement (~25,000 words).

Good luck with your submission! 🎓
