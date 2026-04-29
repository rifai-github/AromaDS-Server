import { PDFDocument } from 'pdf-lib';
import fs from 'fs';
import path from 'path';

/**
 * Merges multiple PDF and Image files into a single PDF.
 * Usage: node pdf-merge.js <outputPath> <file1> <file2> ...
 */
async function mergeFiles() {
    const args = process.argv.slice(2);
    if (args.length < 2) {
        console.error('Usage: node pdf-merge.js <outputPath> <file1> <file2> ...');
        process.exit(1);
    }

    const outputPath = args[0];
    const filePaths = args.slice(1);

    try {
        const mergedPdf = await PDFDocument.create();

        for (const filePath of filePaths) {
            if (!fs.existsSync(filePath)) {
                console.warn(`File not found: ${filePath}`);
                continue;
            }

            const extension = path.extname(filePath).toLowerCase();
            const fileBuffer = fs.readFileSync(filePath);

            if (extension === '.pdf') {
                try {
                    const pdf = await PDFDocument.load(fileBuffer, { ignoreEncryption: true });
                    const copiedPages = await mergedPdf.copyPages(pdf, pdf.getPageIndices());
                    copiedPages.forEach((page) => mergedPdf.addPage(page));
                } catch (pdfErr) {
                    console.error(`Error processing PDF ${filePath}: ${pdfErr.message}`);
                    await addErrorPage(mergedPdf, path.basename(filePath), pdfErr.message);
                }
            } else if (['.jpg', '.jpeg', '.png'].includes(extension)) {
                try {
                    let image;
                    if (extension === '.png') {
                        image = await mergedPdf.embedPng(fileBuffer);
                    } else {
                        image = await mergedPdf.embedJpg(fileBuffer);
                    }

                    const page = mergedPdf.addPage();
                    const { width, height } = page.getSize();
                    const dims = image.scaleToFit(width - 40, height - 40);

                    page.drawImage(image, {
                        x: width / 2 - dims.width / 2,
                        y: height / 2 - dims.height / 2,
                        width: dims.width,
                        height: dims.height,
                    });
                } catch (imgErr) {
                    console.error(`Error processing Image ${filePath}: ${imgErr.message}`);
                }
            }
        }

        const pdfBytes = await mergedPdf.save();
        fs.writeFileSync(outputPath, pdfBytes);
        console.log(`Merged PDF saved to: ${outputPath}`);
    } catch (err) {
        console.error(`Fatal error during merging: ${err.message}`);
        process.exit(1);
    }
}

async function addErrorPage(doc, fileName, error) {
    const page = doc.addPage();
    const { width, height } = page.getSize();
    
    // We don't have standard fonts embedded easily without effort, 
    // but pdf-lib has built-in standard fonts.
    // For simplicity, we just add a page. If needed we could use drawText.
}

mergeFiles();
