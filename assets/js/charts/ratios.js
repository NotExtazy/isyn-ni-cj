// Financial Ratios Dashboard with Needle Pointer Gauges

function createGaugeChart(elementId, value, title, maxValue = 100, isPercentage = false, goodRatioText = '', reversedLogic = false) {
    let displayValue = 0;
    let percentage = 0;
    
    if (value === null || value === undefined || isNaN(value)) {
        value = 0;
    }
    
    // Always use absolute value to show positive
    value = Math.abs(value);
    
    // For percentage ratios, backend already returns percentage (e.g., 11.23)
    // For regular ratios, value is as-is (e.g., 2.36)
    displayValue = value;
    percentage = Math.min(Math.max((value / maxValue) * 100, 0), 100);
    
    const options = {
        series: [0], // Set to 0 to hide the ApexCharts bar
        chart: {
            type: 'radialBar',
            height: 350,
            fontFamily: '"Inter", sans-serif',
        },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                hollow: {
                    margin: 0,
                    size: '70%',
                    background: '#fff',
                },
                track: {
                    show: false, // Hide the track/background
                },
                dataLabels: {
                    show: true,
                    name: {
                        offsetY: 25,
                        show: true,
                        color: '#888',
                        fontSize: '13px',
                        fontWeight: 500
                    },
                    value: {
                        offsetY: 40,
                        color: '#000',
                        fontSize: '32px',
                        fontWeight: 'bold',
                        show: true,
                        formatter: function() {
                            if (isPercentage) {
                                return displayValue.toFixed(0) + '%';
                            } else {
                                return displayValue.toFixed(2);
                            }
                        }
                    }
                }
            }
        },
        fill: {
            type: 'solid',
            colors: ['transparent'] // Make it transparent
        },
        stroke: {
            lineCap: 'butt'
        },
        labels: [title]
    };

    const chart = new ApexCharts(document.querySelector(`#${elementId}`), options);
    chart.render();
    
    // Add status label below the chart
    setTimeout(() => {
        const container = document.querySelector(`#${elementId}`);
        if (container) {
            // Remove existing status label if any
            let existingLabel = container.querySelector('.ratio-status-label');
            if (existingLabel) {
                existingLabel.remove();
            }
            
            // Create status label container (only for good ratio text)
            if (goodRatioText) {
                const statusContainer = document.createElement('div');
                statusContainer.className = 'ratio-status-label';
                statusContainer.style.cssText = `
                    text-align: center;
                    margin-top: -60px;
                    position: relative;
                    z-index: 100;
                `;
                
                // Good ratio benchmark text only
                const benchmarkText = document.createElement('div');
                benchmarkText.style.cssText = `
                    font-size: 12px;
                    font-weight: 500;
                    color: #666;
                `;
                benchmarkText.textContent = 'Good Ratio = ' + goodRatioText;
                statusContainer.appendChild(benchmarkText);
                
                container.appendChild(statusContainer);
            }
        }
    }, 100);
    
    // Add colored sections and needle after chart renders
    setTimeout(() => {
        addColoredSections(elementId, maxValue, isPercentage, reversedLogic);
        addNeedlePointer(elementId, percentage);
    }, 100);
    
    return chart;
}

// Function to add colored sections (red, yellow, green) with scale numbers
function addColoredSections(elementId, maxValue, isPercentage, reversedLogic = false) {
    const container = document.querySelector(`#${elementId}`);
    if (!container) return;
    
    // Remove existing sections
    let sectionsContainer = container.querySelector('.gauge-sections');
    if (sectionsContainer) {
        sectionsContainer.remove();
    }
    
    // Create sections container
    sectionsContainer = document.createElement('div');
    sectionsContainer.className = 'gauge-sections';
    sectionsContainer.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        width: 90%;
        height: 90%;
        transform: translate(-50%, -50%);
        pointer-events: none;
        z-index: 1;
    `;
    
    // Create SVG for colored sections
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('viewBox', '0 0 200 200');
    svg.style.cssText = 'position: absolute; top: 0; left: 0;';
    
    const centerX = 100;
    const centerY = 100;
    const radius = 75;
    const strokeWidth = 22;
    
    if (reversedLogic) {
        // For leverage ratios: Green (low/good) -> Yellow -> Red (high/bad)
        // Green section (0-33%, -90 to -30 degrees)
        const greenPath = describeArc(centerX, centerY, radius, -90, -30);
        const greenSegment = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        greenSegment.setAttribute('d', greenPath);
        greenSegment.setAttribute('fill', 'none');
        greenSegment.setAttribute('stroke', '#22c55e');
        greenSegment.setAttribute('stroke-width', strokeWidth);
        greenSegment.setAttribute('stroke-linecap', 'butt');
        svg.appendChild(greenSegment);
        
        // Yellow section (33-66%, -30 to 30 degrees)
        const yellowPath = describeArc(centerX, centerY, radius, -30, 30);
        const yellowSegment = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        yellowSegment.setAttribute('d', yellowPath);
        yellowSegment.setAttribute('fill', 'none');
        yellowSegment.setAttribute('stroke', '#fbbf24');
        yellowSegment.setAttribute('stroke-width', strokeWidth);
        yellowSegment.setAttribute('stroke-linecap', 'butt');
        svg.appendChild(yellowSegment);
        
        // Red section (66-100%, 30 to 90 degrees)
        const redPath = describeArc(centerX, centerY, radius, 30, 90);
        const redSegment = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        redSegment.setAttribute('d', redPath);
        redSegment.setAttribute('fill', 'none');
        redSegment.setAttribute('stroke', '#ef4444');
        redSegment.setAttribute('stroke-width', strokeWidth);
        redSegment.setAttribute('stroke-linecap', 'butt');
        svg.appendChild(redSegment);
    } else {
        // Normal: Red (low/bad) -> Yellow -> Green (high/good)
        // Red section (0-33%, -90 to -30 degrees)
        const redPath = describeArc(centerX, centerY, radius, -90, -30);
        const redSegment = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        redSegment.setAttribute('d', redPath);
        redSegment.setAttribute('fill', 'none');
        redSegment.setAttribute('stroke', '#ef4444');
        redSegment.setAttribute('stroke-width', strokeWidth);
        redSegment.setAttribute('stroke-linecap', 'butt');
        svg.appendChild(redSegment);
        
        // Yellow section (33-66%, -30 to 30 degrees)
        const yellowPath = describeArc(centerX, centerY, radius, -30, 30);
        const yellowSegment = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        yellowSegment.setAttribute('d', yellowPath);
        yellowSegment.setAttribute('fill', 'none');
        yellowSegment.setAttribute('stroke', '#fbbf24');
        yellowSegment.setAttribute('stroke-width', strokeWidth);
        yellowSegment.setAttribute('stroke-linecap', 'butt');
        svg.appendChild(yellowSegment);
        
        // Green section (66-100%, 30 to 90 degrees)
        const greenPath = describeArc(centerX, centerY, radius, 30, 90);
        const greenSegment = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        greenSegment.setAttribute('d', greenPath);
        greenSegment.setAttribute('fill', 'none');
        greenSegment.setAttribute('stroke', '#22c55e');
        greenSegment.setAttribute('stroke-width', strokeWidth);
        greenSegment.setAttribute('stroke-linecap', 'butt');
        svg.appendChild(greenSegment);
    }
    
    // Add scale numbers
    const scaleRadius = radius + 25; // Position numbers outside the arc
    const numLabels = 5; // Number of scale labels (0, 25%, 50%, 75%, 100%)
    
    for (let i = 0; i <= numLabels; i++) {
        const percent = (i / numLabels) * 100;
        const angle = -90 + (percent / 100) * 180; // Map to -90 to 90 degrees
        const pos = polarToCartesian(centerX, centerY, scaleRadius, angle);
        
        // Calculate the actual value
        let labelValue;
        if (isPercentage) {
            // Backend already returns percentage, so just show portion of maxValue
            labelValue = ((percent / 100) * maxValue).toFixed(0) + '%';
        } else {
            labelValue = ((percent / 100) * maxValue).toFixed(1);
        }
        
        // Create text element
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', pos.x);
        text.setAttribute('y', pos.y);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('dominant-baseline', 'middle');
        text.setAttribute('fill', '#666');
        text.setAttribute('font-size', '8');
        text.setAttribute('font-weight', '600');
        text.textContent = labelValue;
        svg.appendChild(text);
    }
    
    
    // Helper function to place curved text letter by letter
    function addCurvedText(svg, text, centerX, centerY, radius, startAngle, endAngle, color) {
        const angleRange = endAngle - startAngle;
        const angleStep = angleRange / (text.length - 1);
        
        for (let i = 0; i < text.length; i++) {
            const angle = startAngle + (angleStep * i);
            const pos = polarToCartesian(centerX, centerY, radius, angle);
            
            const textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            textElement.setAttribute('x', pos.x);
            textElement.setAttribute('y', pos.y);
            textElement.setAttribute('fill', color);
            textElement.setAttribute('font-size', '10');
            textElement.setAttribute('font-weight', 'bold');
            textElement.setAttribute('text-anchor', 'middle');
            textElement.setAttribute('dominant-baseline', 'middle');
            textElement.setAttribute('transform', `rotate(${angle}, ${pos.x}, ${pos.y})`);
            textElement.setAttribute('style', 'text-shadow: 0 1px 3px rgba(0,0,0,0.5);');
            textElement.textContent = text[i];
            svg.appendChild(textElement);
        }
    }
    
    // Add curved text for each section
    if (reversedLogic) {
        // For leverage ratios: Excellent (left) -> Good (middle) -> Poor (right)
        addCurvedText(svg, 'Excellent', centerX, centerY, radius, -75, -45, '#ffffff');
        addCurvedText(svg, 'Good', centerX, centerY, radius, -15, 15, '#ffffff');
        addCurvedText(svg, 'Poor', centerX, centerY, radius, 45, 75, '#ffffff');
    } else {
        // Normal: Poor (left) -> Good (middle) -> Excellent (right)
        addCurvedText(svg, 'Poor', centerX, centerY, radius, -75, -45, '#ffffff');
        addCurvedText(svg, 'Good', centerX, centerY, radius, -15, 15, '#ffffff');
        addCurvedText(svg, 'Excellent', centerX, centerY, radius, 45, 75, '#ffffff');
    }
    
    sectionsContainer.appendChild(svg);
    container.style.position = 'relative';
    container.appendChild(sectionsContainer);
}

// Helper function to describe an arc path
function describeArc(x, y, radius, startAngle, endAngle) {
    const start = polarToCartesian(x, y, radius, endAngle);
    const end = polarToCartesian(x, y, radius, startAngle);
    const largeArcFlag = endAngle - startAngle <= 180 ? '0' : '1';
    
    return [
        'M', start.x, start.y,
        'A', radius, radius, 0, largeArcFlag, 0, end.x, end.y
    ].join(' ');
}

// Helper function to convert polar coordinates to cartesian
function polarToCartesian(centerX, centerY, radius, angleInDegrees) {
    const angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
    return {
        x: centerX + (radius * Math.cos(angleInRadians)),
        y: centerY + (radius * Math.sin(angleInRadians))
    };
}

// Function to add needle pointer
function addNeedlePointer(elementId, percentage) {
    const container = document.querySelector(`#${elementId}`);
    if (!container) return;
    
    // Remove existing needle
    let needleContainer = container.querySelector('.gauge-needle-container');
    if (needleContainer) {
        needleContainer.remove();
    }
    
    // Create needle container
    needleContainer = document.createElement('div');
    needleContainer.className = 'gauge-needle-container';
    needleContainer.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 100%;
        transform: translate(-50%, -50%);
        pointer-events: none;
        z-index: 10;
    `;
    
    // Calculate needle angle (-90 to 90 degrees)
    const angle = -90 + (percentage / 100) * 180;
    
    // Create needle
    const needle = document.createElement('div');
    needle.className = 'gauge-needle';
    needle.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        width: 4px;
        height: 38%;
        background: linear-gradient(to bottom, #1a1a1a 0%, #4a4a4a 100%);
        transform-origin: bottom center;
        transform: translate(-50%, -100%) rotate(-90deg);
        transition: transform 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        border-radius: 3px 3px 0 0;
    `;
    
    // Animate needle to target angle after a brief delay
    setTimeout(() => {
        needle.style.transform = `translate(-50%, -100%) rotate(${angle}deg)`;
    }, 50);
    
    // Create needle center dot
    const needleCenter = document.createElement('div');
    needleCenter.className = 'gauge-needle-center';
    needleCenter.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        width: 14px;
        height: 14px;
        background: radial-gradient(circle, #2a2a2a 0%, #1a1a1a 100%);
        border: 3px solid #fff;
        border-radius: 50%;
        transform: translate(-50%, -50%);
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        z-index: 11;
    `;
    
    needleContainer.appendChild(needle);
    needleContainer.appendChild(needleCenter);
    container.appendChild(needleContainer);
}

let ratioCharts = {};

function loadRatioData(year) {
    $.ajax({
        url: "/iSynApp-main/routes/dashboard/ratios.route.php",
        type: "POST",
        data: { action: "GetRatioData", year: year },
        dataType: "JSON",
        success: function(response) {
            if (response.STATUS === 'SUCCESS') {
                console.log("=== RATIO DATA DEBUG ===");
                console.log("Raw data from database:", response.DEBUG);
                console.log("Calculated ratios:", response.DATA);
                console.log("ROE Breakdown:");
                console.log("  Net Income After Tax:", response.DEBUG.netIncomeAfterTax);
                console.log("  Equity:", response.DEBUG.equity);
                console.log("  ROE Decimal:", response.DATA.roe);
                console.log("  ROE Percentage:", (response.DATA.roe * 100).toFixed(2) + "%");
                console.log("=======================");
                updateRatioCharts(response.DATA);
            }
        }
    });
}

function updateRatioCharts(data) {
    // Store current data for resize/visibility handlers
    currentRatioData = data;
    
    Object.values(ratioCharts).forEach(chart => { if (chart) chart.destroy(); });
    ratioCharts = {};

    // Liquidity Ratios - backend returns decimals (e.g., 2.36)
    ratioCharts.currentRatio = createGaugeChart('current-ratio-gauge', data.currentRatio || 0, 'Current Ratio', 2, false, '1.2 to 2');
    ratioCharts.acidTest = createGaugeChart('acid-test-ratio-gauge', data.acidTestRatio || 0, 'Acid-Test Ratio', 2, false, '1 or greater');
    ratioCharts.cashRatio = createGaugeChart('cash-ratio-gauge', data.cashRatio || 0, 'Cash Ratio', 1, false, '.05 and 1');
    
    // Profitability Ratios - backend returns decimals (e.g., 0.16), need to convert to percentage for display
    ratioCharts.roa = createGaugeChart('roa-gauge', (data.roa || 0) * 100, 'ROA', 20, true, 'Over 5%');
    ratioCharts.roe = createGaugeChart('roe-gauge', (data.roe || 0) * 100, 'ROE', 20, true, '15%-20%');
    ratioCharts.grossMargin = createGaugeChart('gross-margin-gauge', (data.grossMargin || 0) * 100, 'Gross Margin', 30, true, '10% average 20% high good 5% low');
    ratioCharts.operatingMargin = createGaugeChart('operating-margin-gauge', (data.operatingMargin || 0) * 100, 'Operating Margin', 20, true, '10% considered as average');
    
    // Efficiency Ratios - backend returns decimals
    ratioCharts.assetTurnover = createGaugeChart('asset-turnover-gauge', data.assetTurnover || 0, 'Asset Turnover', 3, false, '2.5 or more is good');
    ratioCharts.receivablesTurnover = createGaugeChart('receivables-turnover-gauge', data.receivablesTurnover || 0, 'Receivables Turnover', 6, false, 'the higher the better');
    ratioCharts.inventoryTurnover = createGaugeChart('inventory-turnover-gauge', data.inventoryTurnover || 0, 'Inventory Turnover', 10, false, 'the higher the better');
    ratioCharts.daysSalesInventory = createGaugeChart('days-sales-inventory-gauge', data.daysSalesInventory || 0, 'Days Sales in Inventory', 100, false, 'a small average of days sales, or low days sales in inventory, indicates that a business is efficient');
    
    // Leverage Ratios - backend returns decimals
    // Debt Ratio: lower is better (reversed logic)
    ratioCharts.debtRatio = createGaugeChart('debt-ratio-gauge', data.debtRatio || 0, 'Debt Ratio', 0.6, false, '0.4 or lower are considered better, while 0.6 or higher makes it more difficult to borrow money', true);
    // Debt to Equity: higher is better (normal logic)
    ratioCharts.debtEquity = createGaugeChart('debt-equity-gauge', data.debtEquityRatio || 0, 'Debt to Equity', 2.5, false, '2 or 2.5 is generally considered good', false);

    $('#total-equity-value').text('₱' + parseFloat(data.totalEquity || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total-shares-value').text(parseFloat(data.totalShares || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}));
    $('#net-market-per-share').text('₱' + parseFloat(data.netMarketPerShare || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}

let currentRatioData = null;

$(document).ready(function() {
    if ($('#ratio-year').length > 0) {
        loadRatioData($('#ratio-year').val());
        $('#ratio-year').on('change', function() {
            loadRatioData($(this).val());
        });
    }
    
    // Handle window resize to redraw charts
    let resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (currentRatioData) {
                updateRatioCharts(currentRatioData);
            }
        }, 250);
    });
    
    // Handle visibility change (tab switching)
    $(document).on('visibilitychange', function() {
        if (!document.hidden && currentRatioData) {
            setTimeout(function() {
                updateRatioCharts(currentRatioData);
            }, 100);
        }
    });
});
