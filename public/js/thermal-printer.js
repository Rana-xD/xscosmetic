/**
 * Thermal Printer Utility for 80mm Receipt Paper
 * Supports browser-based printing with ESC/POS commands
 */

class ThermalPrinter {
    constructor() {
        this.paperWidth = 48; // Characters per line for 80mm paper
        this.exchangeRate = 4100; // USD to Riel exchange rate
    }

    /**
     * Format text to center align
     */
    centerText(text) {
        const padding = Math.max(0, Math.floor((this.paperWidth - text.length) / 2));
        return ' '.repeat(padding) + text;
    }

    /**
     * Format text to right align
     */
    rightText(text) {
        const padding = Math.max(0, this.paperWidth - text.length);
        return ' '.repeat(padding) + text;
    }

    /**
     * Create a line with left and right text
     */
    leftRightText(left, right) {
        const totalLen = left.length + right.length;
        if (totalLen >= this.paperWidth) {
            return left + right;
        }
        const spaces = this.paperWidth - totalLen;
        return left + ' '.repeat(spaces) + right;
    }

    /**
     * Create a separator line
     */
    separator(char = '-') {
        return char.repeat(this.paperWidth);
    }

    /**
     * Format item line for receipt (optimized for 80mm paper)
     * This is kept for backward compatibility but not used in new layout
     */
    formatItemLine(name, price, qty, discount, total) {
        // Wrap product name if too long (max 20 chars for name column)
        const maxNameWidth = 20;
        const wrappedName = this.wrapText(name, maxNameWidth);
        
        // Format values with proper alignment
        const priceStr = `$${price}`.padStart(8);
        const qtyStr = `${qty}`.padStart(4);
        const discountStr = `${discount}%`.padStart(5);
        const totalStr = `$${total}`.padStart(10);
        
        // Create the value line (aligned to the right of name)
        const valueLine = `${priceStr} ${qtyStr} ${discountStr} ${totalStr}`;
        
        // Return name line(s) followed by value line
        return [...wrappedName, valueLine];
    }
    
    /**
     * Wrap text to fit within specified width
     */
    wrapText(text, maxWidth) {
        if (text.length <= maxWidth) {
            return [text];
        }
        
        const words = text.split(' ');
        const lines = [];
        let currentLine = '';
        
        words.forEach(word => {
            if ((currentLine + word).length <= maxWidth) {
                currentLine += (currentLine ? ' ' : '') + word;
            } else {
                if (currentLine) lines.push(currentLine);
                currentLine = word;
            }
        });
        
        if (currentLine) lines.push(currentLine);
        return lines;
    }

    /**
     * Generate receipt HTML for printing
     */
    generateReceiptHTML(invoiceData) {
        let html = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: 80mm auto;
            margin: 0mm;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 3mm;
            width: 80mm;
            max-width: 80mm;
        }
        
        .receipt {
            width: 100%;
            max-width: 74mm;
            margin: 0 auto;
        }
        
        .center {
            text-align: center;
        }
        
        .large {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 5px;
        }
        
        .separator {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        
        td {
            padding: 1px 2px;
            font-size: 10px;
            vertical-align: top;
        }
        
        @media print {
            @page {
                size: 80mm auto;
                margin: 0mm;
            }
            
            body {
                width: 80mm;
                max-width: 80mm;
                padding: 5mm;
                margin: 0;
            }
            
            .receipt {
                max-width: 70mm;
            }
            
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        
        @media screen {
            body {
                background: #f0f0f0;
                padding: 20px;
            }
            
            .receipt {
                background: white;
                padding: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="center large">${invoiceData.store_name}</div>
        
        <table style="width: 100%; margin-top: 3px; margin-bottom: 3px; font-size: 10px;">
            <tr>
                <td style="width: 25%;">Seller:</td>
                <td style="width: 75%; text-align: right;">${invoiceData.cashier || 'manager'}</td>
            </tr>
            <tr>
                <td style="width: 25%;">Date:</td>
                <td style="width: 75%; text-align: right;">${invoiceData.date} / ${invoiceData.time}</td>
            </tr>
        </table>
        
        <div class="separator"></div>
        
        <table style="font-family: 'Courier New', monospace; font-size: 10px; border-collapse: collapse; width: 100%;">
            <tr style="font-weight: bold;">
                <td style="width: 35%; padding-bottom: 3px; padding-right: 5px;">Name</td>
                <td colspan="4" style="padding-bottom: 3px; padding-left: 15px;">
                    <span style="display: inline-block; width: 26%; text-align: right;">Price</span>
                    <span style="display: inline-block; width: 18%; text-align: center;">Qty</span>
                    <span style="display: inline-block; width: 18%; text-align: center;">Dis</span>
                    <span style="display: inline-block; width: 38%; text-align: right;">Total</span>
                </td>
            </tr>`;

        // Add items with proper formatting
        invoiceData.items.forEach((item, index) => {
            const itemName = item.name || item.product_name;
            let price = typeof item.price === 'string' ? item.price.replace('$', '').trim() : item.price;
            let total = typeof item.total === 'string' ? item.total.replace('$', '').trim() : item.total;
            
            // Remove .00 from prices if they are whole numbers
            price = parseFloat(price);
            total = parseFloat(total);
            const priceDisplay = price % 1 === 0 ? price.toFixed(0) : price.toFixed(2);
            const totalDisplay = total % 1 === 0 ? total.toFixed(0) : total.toFixed(2);
            
            // Split product name into lines if it's too long (max ~17 chars for first line to fit with values)
            const maxFirstLineWidth = 17;
            let firstLine = itemName;
            let remainingLines = '';
            
            if (itemName.length > maxFirstLineWidth) {
                // Try to split at word boundary
                const words = itemName.split(' ');
                let tempLine = '';
                let restWords = [];
                let foundBreak = false;
                
                words.forEach(word => {
                    if (!foundBreak && (tempLine + ' ' + word).trim().length <= maxFirstLineWidth) {
                        tempLine += (tempLine ? ' ' : '') + word;
                    } else {
                        foundBreak = true;
                        restWords.push(word);
                    }
                });
                
                firstLine = tempLine || itemName.substring(0, maxFirstLineWidth);
                remainingLines = restWords.join(' ');
            }
            
            // First row: Product name (first line) + values
            html += `
            <tr style="vertical-align: top;">
                <td style="padding-top: ${index === 0 ? '0' : '5'}px; padding-right: 5px; width: 35%;">${firstLine}</td>
                <td style="text-align: right; padding-top: ${index === 0 ? '0' : '5'}px; width: 18%;">$${priceDisplay}</td>
                <td style="text-align: center; padding-top: ${index === 0 ? '0' : '5'}px; width: 12%;">${item.quantity}</td>
                <td style="text-align: center; padding-top: ${index === 0 ? '0' : '5'}px; width: 12%;">${item.discount}%</td>
                <td style="text-align: right; padding-top: ${index === 0 ? '0' : '5'}px; width: 23%;">$${totalDisplay}</td>
            </tr>`;
            
            // Additional rows for wrapped text (if any)
            if (remainingLines) {
                html += `
            <tr>
                <td colspan="5" style="padding-left: 0px; padding-bottom: 3px;">${remainingLines}</td>
            </tr>`;
            }
        });

        html += `
        </table>
        <div class="separator" style="margin-top: 5px;"></div>
        
        <table style="width: 100%; font-family: 'Courier New', monospace; font-size: 10px; margin-top: 3px;">
            <tr>
                <td style="width: 60%;">Discount All:</td>
                <td style="width: 35%; text-align: right;">${invoiceData.total_discount}%</td>
            </tr>
            <tr>
                <td>Total&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;($):</td>
                <td style="text-align: right;">${invoiceData.total}</td>
            </tr>
            <tr>
                <td>Total&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Riel):</td>
                <td style="text-align: right;">${invoiceData.total_riel}</td>
            </tr>
            <tr>
                <td>Recieve&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;($):</td>
                <td style="text-align: right;">${invoiceData.received_in_usd}</td>
            </tr>
            <tr>
                <td>Recieve&nbsp;&nbsp;(Riel):</td>
                <td style="text-align: right;">${invoiceData.received_in_riel}</td>
            </tr>
        </table>
        
        <div class="separator"></div>
        
        <table style="width: 100%; font-family: 'Courier New', monospace; font-size: 10px; margin-top: 3px;">
            <tr>
                <td style="width: 60%;">Change&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;($)</td>
                <td style="width: 5%; text-align: center;">:</td>
                <td style="width: 35%; text-align: right;">${invoiceData.change_in_usd}</td>
            </tr>
            <tr>
                <td>Change&nbsp;&nbsp;&nbsp;(Riel)</td>
                <td style="text-align: center;">:</td>
                <td style="text-align: right;">${invoiceData.change_in_riel}</td>
            </tr>
        </table>
        
        <div class="separator"></div>
        
        <div style="text-align: center; margin-top: 10px;">
            <img src="/img/invoice_image.png" alt="Terms" style="max-width: 100%; width: 100%; height: auto; transform: scale(1.6);" />
        </div>
        
        <div style="height: 20mm;"></div>
    </div>
    
    <script>
        // Show instructions before printing
        window.onload = function() {
            // Check if this is a thermal printer scenario
            const isThermalPrinter = confirm(
                'THERMAL PRINTER SETUP:\\n\\n' +
                'RECOMMENDED: Use System Dialog\\n' +
                '1. Click "Print using system dialog..." at bottom\\n' +
                '2. Select your thermal printer\\n' +
                '3. Paper size will auto-detect to 80mm\\n\\n' +
                'OR in Browser Dialog:\\n' +
                '1. Destination: Select thermal printer (not PDF)\\n' +
                '2. Paper size: Look for 80mm option\\n' +
                '3. Margins: None\\n\\n' +
                'Click OK to continue'
            );
            
            if (isThermalPrinter) {
                window.print();
                // Close window after printing
                setTimeout(function() {
                    window.close();
                }, 100);
            } else {
                window.close();
            }
        };
    </script>
</body>
</html>`;

        return html;
    }

    /**
     * Print invoice using browser print dialog
     */
    printInvoice(invoiceData) {
        const html = this.generateReceiptHTML(invoiceData);
        
        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=300,height=600');
        
        if (!printWindow) {
            alert('Please allow pop-ups to print receipts');
            return false;
        }
        
        printWindow.document.write(html);
        printWindow.document.close();
        
        return true;
    }

    /**
     * Print using ESC/POS commands (for direct USB/Serial connection)
     * This requires a browser extension or WebUSB API support
     */
    async printWithESCPOS(invoiceData) {
        // Check if WebUSB is supported
        if (!navigator.usb) {
            console.warn('WebUSB not supported. Falling back to browser print.');
            return this.printInvoice(invoiceData);
        }

        try {
            // Request USB device - try with filters first
            let device;
            try {
                device = await navigator.usb.requestDevice({
                    filters: [
                        { vendorId: 0x04b8 }, // Epson
                        { vendorId: 0x0519 }, // Star Micronics
                        { vendorId: 0x154f }, // Xprinter
                        { vendorId: 0x1FC9 }, // Generic POS Printer (your printer)
                        { vendorId: 0x0483 }, // STMicroelectronics
                        { vendorId: 0x1a86 }, // QinHeng Electronics
                    ]
                });
            } catch (filterError) {
                // If filtered search fails, show all USB devices
                console.log('Filtered search failed, showing all USB devices');
                device = await navigator.usb.requestDevice({ filters: [] });
            }

            await device.open();
            await device.selectConfiguration(1);
            await device.claimInterface(0);

            // ESC/POS commands
            const ESC = 0x1B;
            const GS = 0x1D;
            
            // Initialize printer
            let commands = [ESC, 0x40];
            
            // Center align
            commands.push(ESC, 0x61, 0x01);
            
            // Store name (large text)
            commands.push(ESC, 0x21, 0x30); // Double height and width
            commands.push(...this.stringToBytes(invoiceData.store_name + '\n'));
            commands.push(ESC, 0x21, 0x00); // Normal text
            
            // Left align for seller and date
            commands.push(ESC, 0x61, 0x00);
            commands.push(...this.stringToBytes(this.leftRightText('Seller:', invoiceData.cashier || 'manager') + '\n'));
            commands.push(...this.stringToBytes(this.leftRightText('Date:', `${invoiceData.date} / ${invoiceData.time}`) + '\n'));
            
            // Separator
            commands.push(...this.stringToBytes(this.separator('-') + '\n'));
            
            // Product table header - Name stays left, values shifted right
            const headerLine = 'Name' + ' '.repeat(18) + 'Price Qty  Dis    Total';
            commands.push(...this.stringToBytes(headerLine + '\n'));
            
            // Items - first line of name with values, wrapped lines below
            invoiceData.items.forEach((item, index) => {
                const itemName = item.name || item.product_name;
                let price = typeof item.price === 'string' ? item.price.replace('$', '').trim() : item.price;
                let total = typeof item.total === 'string' ? item.total.replace('$', '').trim() : item.total;
                
                // Remove .00 from prices if they are whole numbers
                price = parseFloat(price);
                total = parseFloat(total);
                const priceDisplay = price % 1 === 0 ? price.toFixed(0) : price.toFixed(2);
                const totalDisplay = total % 1 === 0 ? total.toFixed(0) : total.toFixed(2);
                
                // Split product name if too long (max ~17 chars for first line)
                const maxFirstLineWidth = 17;
                let firstLine = itemName;
                let remainingText = '';
                
                if (itemName.length > maxFirstLineWidth) {
                    const words = itemName.split(' ');
                    let tempLine = '';
                    let restWords = [];
                    let foundBreak = false;
                    
                    words.forEach(word => {
                        if (!foundBreak && (tempLine + ' ' + word).trim().length <= maxFirstLineWidth) {
                            tempLine += (tempLine ? ' ' : '') + word;
                        } else {
                            foundBreak = true;
                            restWords.push(word);
                        }
                    });
                    
                    firstLine = tempLine || itemName.substring(0, maxFirstLineWidth);
                    remainingText = restWords.join(' ');
                }
                
                // Format values
                const priceStr = `$${priceDisplay}`.padStart(8);
                const qtyStr = `${item.quantity}`.padStart(3);
                const discountStr = `${item.discount}%`.padStart(4);
                const totalStr = `$${totalDisplay}`.padStart(10);
                
                // First line with name and values - keep name fixed width
                const nameWidth = 18;
                const namePadded = firstLine.padEnd(nameWidth, ' ');
                const firstLineFormatted = namePadded + priceStr + ' ' + qtyStr + ' ' + discountStr + ' ' + totalStr;
                commands.push(...this.stringToBytes(firstLineFormatted + '\n'));
                
                // Wrapped lines (if any)
                if (remainingText) {
                    commands.push(...this.stringToBytes(remainingText + '\n'));
                }
            });
            
            // Separator
            commands.push(...this.stringToBytes(this.separator('-') + '\n'));
            
            // Totals section
            commands.push(...this.stringToBytes(this.leftRightText('Discount All    :', `${invoiceData.total_discount}%`) + '\n'));
            commands.push(...this.stringToBytes(this.leftRightText('Total        ($):', `${invoiceData.total}`) + '\n'));
            commands.push(...this.stringToBytes(this.leftRightText('Total     (Riel):', `${invoiceData.total_riel}`) + '\n'));
            commands.push(...this.stringToBytes(this.leftRightText('Recieve      ($):', `${invoiceData.received_in_usd}`) + '\n'));
            commands.push(...this.stringToBytes(this.leftRightText('Recieve   (Riel):', `${invoiceData.received_in_riel}`) + '\n'));
            
            // Separator
            commands.push(...this.stringToBytes(this.separator('-') + '\n'));
            
            // Change
            commands.push(...this.stringToBytes(this.leftRightText('Change       ($):', `${invoiceData.change_in_usd}`) + '\n'));
            commands.push(...this.stringToBytes(this.leftRightText('Change    (Riel):', `${invoiceData.change_in_riel}`) + '\n'));
            
            // Separator
            commands.push(...this.stringToBytes(this.separator('-') + '\n\n'));
            
            // Print image for Khmer text (footer)
            try {
                const imageData = await this.loadAndConvertImage('/img/invoice_image.png');
                if (imageData) {
                    // Center align for image
                    commands.push(ESC, 0x61, 0x01);
                    // Print image commands
                    commands.push(...imageData);
                    // Left align back
                    commands.push(ESC, 0x61, 0x00);
                }
            } catch (error) {
                console.log('Could not load footer image, skipping');
            }
            commands.push(...this.stringToBytes('\n'));
            
            // Feed paper and cut
            commands.push(0x0A, 0x0A, 0x0A, 0x0A); // Line feeds
            commands.push(GS, 0x56, 0x00); // Full cut
            
            // Send commands to printer
            const data = new Uint8Array(commands);
            await device.transferOut(1, data);
            
            // Close device
            await device.close();
            
            return true;
        } catch (error) {
            console.error('ESC/POS printing failed:', error);
            // Fallback to browser print
            return this.printInvoice(invoiceData);
        }
    }

    /**
     * Convert string to byte array
     */
    stringToBytes(str) {
        const encoder = new TextEncoder();
        return Array.from(encoder.encode(str));
    }

    /**
     * Load and convert image to ESC/POS bitmap format
     */
    async loadAndConvertImage(imagePath) {
        try {
            // Load image
            const img = await this.loadImage(imagePath);
            
            // Create canvas and convert to monochrome bitmap
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas size (max width for 80mm printer is ~576 pixels)
            const maxWidth = 550; // Increased 60% more for larger image
            const scale = Math.min(1.6, maxWidth / img.width); // Allow upscaling
            canvas.width = Math.floor(img.width * scale);
            canvas.height = Math.floor(img.height * scale);
            
            // Draw image on canvas
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            
            // Get image data
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            
            // Convert to monochrome bitmap
            return this.imageDataToESCPOS(imageData);
        } catch (error) {
            console.error('Image loading failed:', error);
            return null;
        }
    }

    /**
     * Load image from URL
     */
    loadImage(url) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = url;
        });
    }

    /**
     * Convert image data to ESC/POS bitmap commands
     */
    imageDataToESCPOS(imageData) {
        const width = imageData.width;
        const height = imageData.height;
        const data = imageData.data;
        
        // Convert to monochrome (1 bit per pixel)
        const threshold = 128;
        const monoData = [];
        
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x += 8) {
                let byte = 0;
                for (let bit = 0; bit < 8; bit++) {
                    const px = x + bit;
                    if (px < width) {
                        const idx = (y * width + px) * 4;
                        const r = data[idx];
                        const g = data[idx + 1];
                        const b = data[idx + 2];
                        const brightness = (r + g + b) / 3;
                        
                        // Invert: black pixels = 1, white pixels = 0
                        if (brightness < threshold) {
                            byte |= (1 << (7 - bit));
                        }
                    }
                }
                monoData.push(byte);
            }
        }
        
        // Create ESC/POS bitmap command
        const commands = [];
        const ESC = 0x1B;
        const GS = 0x1D;
        
        // Calculate width in bytes
        const widthBytes = Math.ceil(width / 8);
        const widthLSB = widthBytes & 0xFF;
        const widthMSB = (widthBytes >> 8) & 0xFF;
        const heightLSB = height & 0xFF;
        const heightMSB = (height >> 8) & 0xFF;
        
        // GS v 0 - Print raster bitmap
        // Format: GS v 0 m xL xH yL yH d1...dk
        commands.push(GS, 0x76, 0x30, 0x00); // GS v 0, normal mode
        commands.push(widthLSB, widthMSB);    // Width in bytes
        commands.push(heightLSB, heightMSB);  // Height in dots
        commands.push(...monoData);           // Bitmap data
        
        return commands;
    }

    /**
     * Print invoice directly to USB thermal printer
     */
    async print(invoiceData) {
        // Directly print to USB thermal printer
        try {
            return await this.printWithESCPOS(invoiceData);
        } catch (error) {
            console.error('USB printing failed:', error);
            alert('USB printer not found or connection failed. Please check printer connection.');
            return false;
        }
    }
}

// Export for use in other scripts
window.ThermalPrinter = ThermalPrinter;
