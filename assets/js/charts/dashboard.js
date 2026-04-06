(function (jQuery) {
  "use strict";

  // Removed unused #myChart and #d-activity logic

if (document.querySelectorAll('#d-main').length) {
    const options = {
        series: [], // Series data will be loaded dynamically
        chart: {
            fontFamily: '"Inter", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
            height: 230, // Reduced height for more compact display
            type: 'bar',
            toolbar: {
                show: false
            },
            sparkline: {
                enabled: false,
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: { enabled: true, delay: 150 },
                dynamicAnimation: { enabled: true, speed: 350 }
            }
        },
        colors: ["#3a57e8", "#06b6d4"], // Blue for current year, Cyan for comparison year (matching image format)
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '35%', // Even narrower bars like in the image
                borderRadius: 2, // Slight rounded corners
                distributed: false, // Unified color per series
                dataLabels: {
                    position: 'top', 
                    enabled: false // Disable data labels on bars for cleaner look
                },
                rangeBarGroupRows: false,
                rangeBarOverlap: true,
            },
        },
        annotations: {
            yaxis: [
                {
                    y: 0,
                    borderColor: '#2c3e50',
                    borderWidth: 1,
                    strokeDashArray: 0,
                    opacity: 0.5
                }
            ]
        },
        dataLabels: {
            enabled: false,
            formatter: function (val) {
                return "₱" + parseFloat(val).toLocaleString('en-US', { maximumFractionDigits: 0 });
            },
            offsetY: -12,
            style: {
                fontSize: '12px',
                fontWeight: 600,
                colors: ['#304758']
            }
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: ["ASSETS", "LIABILITIES", "EQUITY"],
            labels: {
                minHeight: 22,
                maxHeight: 22,
                style: {
                    colors: "#2c3e50",
                    fontSize: '10px',
                    fontFamily: '"Inter", sans-serif',
                    fontWeight: '600'
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
              show: true,
              labels: {
                  show: true,
                  style: {
                      colors: "#8A92A6",
                      fontSize: '11px',
                      fontFamily: '"Inter", sans-serif',
                  },
                  formatter: (val) => {
                      if (Math.abs(val) >= 1000000) return "₱" + (val / 1000000).toFixed(1) + 'M';
                      if (Math.abs(val) >= 1000) return "₱" + (val / 1000).toFixed(1) + 'k';
                      return "₱" + val.toFixed(0);
                  }
              },
              forceNiceScale: true, // Makes the scale look cleaner
          },
        fill: {
            opacity: 1
        },
        tooltip: {
            enabled: true,
            shared: true,
            intersect: false,
            y: {
                formatter: function (val) {
                    return "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            }
        },
        legend: {
            show: false, // Disable legend to remove Year 1 and Year 2 labels
            position: 'top',
            horizontalAlign: 'center', // Center align for better balance
            fontSize: '14px',
            fontWeight: '600',
            itemMargin: {
                horizontal: 20,
                vertical: 5
            },
            markers: {
                width: 12,
                height: 12,
                radius: 6
            }
        },
        grid: {
            borderColor: '#f1f1f1',
            padding: { left: 0, right: 0, top: 0, bottom: 0 }
        }
    };

    const chart = new ApexCharts(document.querySelector("#d-main"), options);
    chart.render();

  // Function to fetch data
  function loadChartData(year1, year2 = null) {
      // Create an array of promises for data fetching
      const requests = [];
      
      // Request for Year 1
      requests.push($.ajax({
          url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
          type: "POST",
          data: { action: "GetMainChartData", year: year1 },
          dataType: "JSON"
      }));

      // Request for Year 2 (if selected)
      if (year2) {
          requests.push($.ajax({
              url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
              type: "POST",
              data: { action: "GetMainChartData", year: year2 },
              dataType: "JSON"
          }));
      }

      // Wait for all requests to finish
      Promise.all(requests).then(responses => {
          console.log("Server responses:", responses);
          const res1 = responses[0];
          const res2 = responses[1] || null; // Will be undefined if only 1 request
          
          console.log("Year 1 data structure:", JSON.stringify(res1, null, 2));
          console.log("Year 2 data structure:", JSON.stringify(res2, null, 2));
          
          // Check accounting equation validation if available
          if (res1.VALIDATION) {
              console.log("=== ACCOUNTING EQUATION VALIDATION ===");
              console.log("Detailed breakdown:");
              res1.VALIDATION.forEach(month => {
                  const status = month.balanced ? '✅ BALANCED' : '❌ NOT BALANCED';
                  console.log(`Month ${month.month}:`);
                  console.log(`  Assets: ${month.assets.toLocaleString('en-US', {minimumFractionDigits: 2})}`);
                  console.log(`  Liabilities: ${month.liabilities.toLocaleString('en-US', {minimumFractionDigits: 2})}`);
                  console.log(`  Equity: ${month.equity.toLocaleString('en-US', {minimumFractionDigits: 2})}`);
                  console.log(`  Liabilities + Equity: ${month.liabilities_plus_equity.toLocaleString('en-US', {minimumFractionDigits: 2})}`);
                  console.log(`  Difference: ${month.difference.toLocaleString('en-US', {minimumFractionDigits: 2})}`);
                  console.log(`  Status: ${status}`);
                  console.log('---');
              });
              
              // Count balanced vs unbalanced months
              const balancedMonths = res1.VALIDATION.filter(m => m.balanced).length;
              const totalMonths = res1.VALIDATION.length;
              console.log(`Summary: ${balancedMonths}/${totalMonths} months balanced`);
              
              // Show problematic months
              const problemMonths = res1.VALIDATION.filter(m => !m.balanced);
              if (problemMonths.length > 0) {
                  console.warn("⚠️ ACCOUNTING EQUATION NOT BALANCING - Problem months:");
                  problemMonths.forEach(month => {
                      console.warn(`Month ${month.month}: Difference = ₱${Math.abs(month.difference).toLocaleString('en-US', {minimumFractionDigits: 2})}`);
                  });
                  
                  // Analyze the pattern
                  const differences = problemMonths.map(m => m.difference);
                  const avgDifference = differences.reduce((a, b) => a + b, 0) / differences.length;
                  console.warn(`Average difference: ₱${Math.abs(avgDifference).toLocaleString('en-US', {minimumFractionDigits: 2})}`);
                  
                  // Determine if assets > liabilities+equity or vice versa
                  const negativeDifferences = differences.filter(d => d < 0).length;
                  const positiveDifferences = differences.filter(d => d > 0).length;
                  
                  if (negativeDifferences > positiveDifferences) {
                      console.warn("Pattern: Liabilities + Equity > Assets (missing assets or excess liabilities/equity)");
                  } else if (positiveDifferences > negativeDifferences) {
                      console.warn("Pattern: Assets > Liabilities + Equity (missing liabilities/equity or excess assets)");
                  }
                  
                  if (Math.abs(avgDifference) < 1000) {
                      console.warn("Small differences suggest rounding or minor data issues");
                  } else if (Math.abs(avgDifference) < 100000) {
                      console.warn("Medium differences suggest account classification issues");
                  } else {
                      console.warn("Large differences suggest major formula or data problems");
                  }
              }
          }
          
          // Check if GROSS, NET, EQUITY arrays exist and have data
          console.log("Year 1 GROSS array:", res1.GROSS);
          console.log("Year 1 NET array:", res1.NET);
          console.log("Year 1 EQUITY array:", res1.EQUITY);
          console.log("Year 1 DONATION array:", res1.DONATION);
          console.log("Year 1 PROVISION array:", res1.PROVISION);
          
          // Check Year 2 data structure
          if (res2) {
              console.log("Year 2 GROSS array:", res2.GROSS);
              console.log("Year 2 NET array:", res2.NET);
              console.log("Year 2 EQUITY array:", res2.EQUITY);
              console.log("Year 2 DONATION array:", res2.DONATION);
              console.log("Year 2 PROVISION array:", res2.PROVISION);
          } else {
              console.log("Year 2 data is null or undefined");
          }

          const grossTotal1 = Math.abs(res1.GROSS.reduce((a, b) => a + b, 0)); // Ensure positive for ASSETS
          const netTotal1 = Math.abs(res1.NET.reduce((a, b) => a + b, 0)); // Ensure positive for LIABILITIES  
          const equityTotal1 = Math.abs(res1.EQUITY ? res1.EQUITY.reduce((a, b) => a + b, 0) : 0); // Ensure positive for EQUITY
          
          // Initialize Year 2 variables to prevent reference error
          let grossTotal2 = 0;
          let netTotal2 = 0;
          let equityTotal2 = 0;
          
          console.log("Calculated Year 1 totals:", {
              grossTotal1, netTotal1, equityTotal1
          });

          let series = [];
          
          if (!res2) {
             // Single Year View
             chart.updateOptions({
                 xaxis: {
                     categories: ['ASSETS', 'LIABILITIES', 'EQUITY']
                 }
             });
             
             series = [{
                 name: `${year1} - January`,
                 data: [grossTotal1, netTotal1, equityTotal1]
             }];
             
          } else {
             // Comparison View: Group by metrics with years side by side (matching image format)
             grossTotal2 = Math.abs(res2.GROSS.reduce((a, b) => a + b, 0)); // Ensure positive for ASSETS
             netTotal2 = Math.abs(res2.NET.reduce((a, b) => a + b, 0)); // Ensure positive for LIABILITIES  
             equityTotal2 = Math.abs(res2.EQUITY ? res2.EQUITY.reduce((a, b) => a + b, 0) : 0); // Ensure positive for EQUITY

             // Update X-Axis Categories to show metrics
             chart.updateOptions({
                 xaxis: {
                     categories: ['ASSETS', 'LIABILITIES', 'EQUITY']
                 }
             });

             series = [{
                 name: `${year1} - January`,
                 data: [grossTotal1, netTotal1, equityTotal1]
             }, {
                 name: `${year2} - January`,
                 data: [grossTotal2, netTotal2, equityTotal2]
             }];
          }

          chart.updateSeries(series);

          // Calculate and update total display
          const total1 = grossTotal1 + netTotal1 + equityTotal1;
          const total2 = year2 ? (grossTotal2 + netTotal2 + equityTotal2) : 0;
          const totalText = year2 ? `Total: ₱${total1.toLocaleString()} vs ₱${total2.toLocaleString()}` : `Total: ₱${total1.toLocaleString()}`;
          
          // Remove total annotation for bar chart
          chart.updateOptions({ annotations: { points: [] } });

          // Update Financial Balance Sheet Data Table
          console.log("Updating table with data:", {
              year1, grossTotal1, netTotal1, equityTotal1, 
              year2, grossTotal2, netTotal2, equityTotal2
          });
          updateFinancialBalanceSheetTable(year1, grossTotal1, netTotal1, equityTotal1, year2, grossTotal2, netTotal2, equityTotal2);

          // Update Titles to match "STATEMENT OF FINANCIAL CONDITION"
          const ratioTitle = document.querySelector("#title-liquidity");
          if (ratioTitle) {
              let titleHtml = `STATEMENT OF FINANCIAL CONDITION`;
              ratioTitle.innerHTML = titleHtml;
          }

          // Update other charts based on Year 1 (Main Year)
          const y1Data = {
              revenue: res1.REVENUE_DATA,
              expenses: res1.EXPENSES,
              income: res1.INCOME,
              ar: res1.AR,
              ap: res1.AP,
              budget: res1.BUDGET,
              donation: res1.DONATION,
              provision: res1.PROVISION
          };
          
          const y2Data = res2 ? {
              revenue: res2.REVENUE_DATA,
              expenses: res2.EXPENSES,
              income: res2.INCOME,
              ar: res2.AR,
              ap: res2.AP,
              budget: res2.BUDGET,
              donation: res2.DONATION,
              provision: res2.PROVISION
          } : null;

          console.log("=== Y1DATA OBJECT ===");
          console.log("y1Data.donation:", y1Data.donation);
          console.log("y1Data.provision:", y1Data.provision);
          console.log("y1Data keys:", Object.keys(y1Data));
          
          if (y2Data) {
              console.log("=== Y2DATA OBJECT ===");
              console.log("y2Data.donation:", y2Data.donation);
              console.log("y2Data.provision:", y2Data.provision);
              console.log("y2Data keys:", Object.keys(y2Data));
          }

          loadARAPChart(y1Data, y2Data, year1, year2);
          loadBudgetChart(y1Data, y2Data, year1, year2);
          loadNetIncomeChart(y1Data, y2Data, year1, year2);
          loadTopSalesChart(year1);

      }).catch(error => {
          console.error("Chart data fetch failed:", error);
      });
  }

  // Function to update Financial Balance Sheet Data Table
  function updateFinancialBalanceSheetTable(year1, assets1, liability1, equity1, year2, assets2, liability2, equity2) {
      console.log("=== TABLE UPDATE FUNCTION CALLED ===");
      console.log("Table update called with:", { year1, assets1, liability1, equity1, year2, assets2, liability2, equity2 });
      
      // Check if table elements exist at all
      const tableExists = document.querySelector("#financial-balance-sheet-table");
      console.log("Table exists:", !!tableExists);
      
      // Update Year 1 row (first row - blue color)
      const year1AssetsEl = document.querySelector("#year1-assets");
      const year1LiabilityEl = document.querySelector("#year1-liability");
      const year1EquityEl = document.querySelector("#year1-equity");
      
      console.log("Table elements found:", {
          year1AssetsEl: !!year1AssetsEl,
          year1LiabilityEl: !!year1LiabilityEl,
          year1EquityEl: !!year1EquityEl
      });
      
      if (year1AssetsEl) {
          const value = "₱" + parseFloat(assets1).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          console.log("Setting Year 1 ASSETS to:", value);
          year1AssetsEl.textContent = value;
      }
      if (year1LiabilityEl) {
          const value = "₱" + parseFloat(liability1).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          console.log("Setting Year 1 LIABILITIES to:", value);
          year1LiabilityEl.textContent = value;
      }
      if (year1EquityEl) {
          const value = "₱" + parseFloat(equity1).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          console.log("Setting Year 1 EQUITY to:", value);
          year1EquityEl.textContent = value;
      }
      
      // Update Year 1 label
      const year1LabelEl = document.querySelector("#year1-label");
      if (year1LabelEl) {
          year1LabelEl.textContent = year1 + ' January';
      }
      
      // Update Year 2 data (second row - red color)
      if (year2 && assets2 !== undefined && liability2 !== undefined && equity2 !== undefined) {
          const year2AssetsEl = document.querySelector("#year2-assets");
          const year2LiabilityEl = document.querySelector("#year2-liability");
          const year2EquityEl = document.querySelector("#year2-equity");
          
          if (year2AssetsEl) {
              const value = "₱" + parseFloat(assets2).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              console.log("Setting Year 2 ASSETS to:", value);
              year2AssetsEl.textContent = value;
          }
          if (year2LiabilityEl) {
              const value = "₱" + parseFloat(liability2).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              console.log("Setting Year 2 LIABILITIES to:", value);
              year2LiabilityEl.textContent = value;
          }
          if (year2EquityEl) {
              const value = "₱" + parseFloat(equity2).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              console.log("Setting Year 2 EQUITY to:", value);
              year2EquityEl.textContent = value;
          }
          
          // Update Year 2 label
          const year2LabelEl = document.querySelector("#year2-label");
          if (year2LabelEl) {
              year2LabelEl.textContent = year2 + ' January';
          }
      } else {
          // Clear Year 2 data if no comparison year
          const year2AssetsEl = document.querySelector("#year2-assets");
          const year2LiabilityEl = document.querySelector("#year2-liability");
          const year2EquityEl = document.querySelector("#year2-equity");
          
          if (year2AssetsEl) year2AssetsEl.textContent = "₱0.00";
          if (year2LiabilityEl) year2LiabilityEl.textContent = "₱0.00";
          if (year2EquityEl) year2EquityEl.textContent = "₱0.00";
      }
  }

  
  // Removed Revenue Chart Logic
  // Removed Net Income Chart Logic

  // Combined AR & AP Chart (Annual Totals)
  let arapChartInstance = null;
  function loadARAPChart(y1Data, y2Data, year1, year2) {
      if (!document.querySelector("#d-arap")) return;

      const ar1 = y1Data.ar || [];
      const ap1 = y1Data.ap || [];
      
      // Fix NaN issues - ensure arrays contain valid numbers
      const validAR1 = ar1.map(val => isNaN(parseFloat(val)) || val === null || val === undefined ? 0 : parseFloat(val));
      const validAP1 = ap1.map(val => isNaN(parseFloat(val)) || val === null || val === undefined ? 0 : parseFloat(val));
      
      const totalAR1 = validAR1.reduce((a, b) => a + b, 0);
      const totalAP1 = validAP1.reduce((a, b) => a + b, 0);
      
      let totalAR2 = 0;
      let totalAP2 = 0;
      
      // Restructure data for proper centering - use single series approach
      let series;
      
      if (y2Data) {
          const ar2 = y2Data.ar || [];
          const ap2 = y2Data.ap || [];
          
          // Fix NaN issues for Year 2 as well
          const validAR2 = ar2.map(val => isNaN(parseFloat(val)) || val === null || val === undefined ? 0 : parseFloat(val));
          const validAP2 = ap2.map(val => isNaN(parseFloat(val)) || val === null || val === undefined ? 0 : parseFloat(val));
          
          totalAR2 = validAR2.reduce((a, b) => a + b, 0);
          totalAP2 = validAP2.reduce((a, b) => a + b, 0);
          
          // Two years comparison - group by category
          series = [
              {
                  name: `${year1} January`,
                  data: [Math.abs(totalAR1), Math.abs(totalAP1)]
              },
              {
                  name: `${year2} January`, 
                  data: [Math.abs(totalAR2), Math.abs(totalAP2)]
              }
          ];
      } else {
          // Single year - one bar per category
          series = [
              {
                  name: `${year1} January`,
                  data: [Math.abs(totalAR1), Math.abs(totalAP1)]
              }
          ];
      }
        
      // Categories for Receivables and Payables layout
      const categories = ['Receivables', 'Payables'];

      const titleEl = document.querySelector("#title-arap");
      if(titleEl) {
           titleEl.innerHTML = `RECEIVABLES & PAYABLES`;
      }

      if (arapChartInstance) {
          arapChartInstance.destroy();
      }

      const options = {
          series: series,
          chart: {
              fontFamily: '"Inter", sans-serif',
              height: 230,
              type: 'bar', 
              toolbar: { show: false },
              sparkline: { enabled: false },
              animations: {
                  enabled: true,
                  easing: 'easeinout',
                  speed: 800,
                  animateGradually: { enabled: true, delay: 150 },
                  dynamicAnimation: { enabled: true, speed: 350 }
              },
          },
          // Colors: Year 1 (Blue), Year 2 (Cyan) - consistent across receivables and payables
          colors: y2Data ? ["#3a57e8", "#06b6d4", "#3a57e8", "#06b6d4"] : ["#3a57e8", "#06b6d4"], 
          plotOptions: {
              bar: {
                  horizontal: false, 
                  columnWidth: '60%',
                  borderRadius: 2,
                  distributed: false,
                  grouped: true,
                  dataLabels: {
                    position: 'top',
                  },
              },
          },
          
          dataLabels: { 
              enabled: false,
              formatter: function (val) {
                  return "₱" + parseFloat(val).toLocaleString('en-US', { maximumFractionDigits: 0 });
              },
              style: {
                  fontSize: '12px',
                  fontWeight: 600
              },
              offsetY: -20
          },
          stroke: {
              show: true,
              width: 2,
              colors: ['transparent']
          },
          xaxis: {
              categories: categories,
              labels: {
                  style: { colors: "#64748b", fontSize: '10px', fontFamily: 'Inter, sans-serif', fontWeight: 600 }
              },
              axisBorder: { show: false },
              axisTicks: { show: false }
          },
          yaxis: {
              show: true,
              labels: {
                  style: { colors: "#64748b", fontSize: '11px', fontFamily: 'Inter, sans-serif' },
                  formatter: (val) => {
                      if (val >= 1000000) return "₱" + (val / 1000000).toFixed(1) + 'M';
                      if (val >= 1000) return "₱" + (val / 1000).toFixed(1) + 'k';
                      return "₱" + val;
                  }
              }
          },
          grid: {
              show: true,
              borderColor: '#e2e8f0',
              strokeDashArray: 5,
              padding: { left: 0, right: 0, top: 0, bottom: 0 }
          },
          legend: {
              show: false,
              position: 'top',
              horizontalAlign: 'right', 
          },
          tooltip: {
              enabled: true,
              shared: false,
              intersect: true,
              theme: 'light',
              y: {
                  formatter: function(val) {
                      return "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                  }
              }
          }
      };

      arapChartInstance = new ApexCharts(document.querySelector("#d-arap"), options);
      arapChartInstance.render();
      
      // Calculate totals
      const total1 = totalAR1 + totalAP1;
      const total2 = y2Data ? (totalAR2 + totalAP2) : 0;
      const totalText = y2Data ? `Total: ₱${total1.toLocaleString()} vs ₱${total2.toLocaleString()}` : `Total: ₱${total1.toLocaleString()}`;
      
      // No annotation label for Receivables & Payables per request
      
      // Update Receivables & Payables table with totals
      updateReceivablesPayablesTable(year1, totalAR1, totalAP1, y2Data ? year2 : null, totalAR2, totalAP2);
  }
  
  // Function to update Receivables & Payables Data Table
  function updateReceivablesPayablesTable(year1, receivables1, payables1, year2, receivables2, payables2) {
      console.log("=== RECEIVABLES & PAYABLES TABLE UPDATE ===");
      console.log("Table update called with:", { year1, receivables1, payables1, year2, receivables2, payables2 });
      
      // Check if table elements exist
      const tableExists = document.querySelector("#receivables-payables-table");
      if (!tableExists) {
          console.log("Receivables & Payables table not found");
          return;
      }
      
      // Update Year 1 data
      const year1ReceivablesEl = document.querySelector("#year1-receivables");
      const year1PayablesEl = document.querySelector("#year1-payables");
      const year1LabelEl = document.querySelector("#receivables-year1-label");
      
      // Update Year 1 label
      if (year1LabelEl) {
          year1LabelEl.textContent = year1 + ' January';
      }
      
      if (year1ReceivablesEl) {
          year1ReceivablesEl.textContent = "₱" + Math.abs(parseFloat(receivables1 || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      }
      if (year1PayablesEl) {
          year1PayablesEl.textContent = "₱" + Math.abs(parseFloat(payables1 || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      }
      
      // Update Year 2 data (only if year2 exists)
      if (year2 && receivables2 !== undefined && payables2 !== undefined) {
          const year2ReceivablesEl = document.querySelector("#year2-receivables");
          const year2PayablesEl = document.querySelector("#year2-payables");
          const year2LabelEl = document.querySelector("#receivables-year2-label");
          
          // Update Year 2 label
          if (year2LabelEl) {
              year2LabelEl.textContent = year2 + ' January';
          }
          
          if (year2ReceivablesEl) {
              year2ReceivablesEl.textContent = "₱" + Math.abs(parseFloat(receivables2 || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          }
          if (year2PayablesEl) {
              year2PayablesEl.textContent = "₱" + Math.abs(parseFloat(payables2 || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          }
      } else {
          // Clear Year 2 data if no comparison year
          const year2ReceivablesEl = document.querySelector("#year2-receivables");
          const year2PayablesEl = document.querySelector("#year2-payables");
          
          if (year2ReceivablesEl) year2ReceivablesEl.textContent = "₱0.00";
          if (year2PayablesEl) year2PayablesEl.textContent = "₱0.00";
      }
  }
  
  // Budget Chart Logic
  let budgetChartInstance = null;
  function loadBudgetChart(y1Data, y2Data, year1, year2) {
      if (!document.querySelector("#d-budget")) return;

      const incomeData1 = y1Data.budget ? y1Data.budget.INCOME : [];
      const expenseData1 = y1Data.budget ? y1Data.budget.EXPENSE : [];
      
      const titleEl = document.querySelector("#title-budget");
      if(titleEl) {
           let titleHtml = `Budget Overview (${year1})`;
           if (y2Data) {
               titleHtml += ` <small class="text-muted">vs ${year2}</small>`;
           }
           titleEl.innerHTML = titleHtml;
      }

      let series = [];
      if (!y2Data) {
          series = [{
              name: 'Income Budget',
              data: incomeData1
          }, {
              name: 'Expenses Budget',
              data: expenseData1
          }];
      } else {
          const incomeData2 = y2Data.budget ? y2Data.budget.INCOME : [];
          const expenseData2 = y2Data.budget ? y2Data.budget.EXPENSE : [];
          series = [{
              name: `${year1} January Income Budget`,
              data: incomeData1
          }, {
              name: `${year2} January Income Budget`,
              data: incomeData2
          }, {
              name: `${year1} January Expenses Budget`,
              data: expenseData1
          }, {
              name: `${year2} January Expenses Budget`,
              data: expenseData2
          }];
      }

      if (budgetChartInstance) {
          budgetChartInstance.destroy();
      }

      const options = {
          series: series,
          chart: {
              fontFamily: '"Inter", sans-serif',
              height: 350,
              type: 'bar',
              toolbar: { show: false },
              sparkline: { enabled: false },
              animations: {
                  enabled: true,
                  easing: 'easeinout',
                  speed: 800,
                  animateGradually: { enabled: true, delay: 150 },
                  dynamicAnimation: { enabled: true, speed: 350 }
              },
          },
          colors: !y2Data ? ["#3a57e8", "#06b6d4"] : ["#3a57e8", "#06b6d4", "#3a57e8", "#06b6d4"], // Blue for Year 1, Cyan for Year 2
          plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 0,
                dataLabels: {
                    position: 'top', // top, center, bottom
                },
            },
        },
        
          dataLabels: { 
              enabled: false,
              formatter: function (val) {
                  return "₱" + parseFloat(val).toLocaleString('en-US', { maximumFractionDigits: 0 });
              },
              style: {
                  fontSize: '12px',
                  fontWeight: 600
              },
              offsetY: -20
          },
          stroke: {
              show: true,
              width: 2,
              colors: ['transparent']
          },
          xaxis: {
              categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
              labels: {
                  style: { colors: "#64748b", fontSize: '11px', fontFamily: 'Inter, sans-serif' }
              },
              axisBorder: { show: false },
              axisTicks: { show: false }
          },
          yaxis: {
              show: true,
              labels: {
                  style: { colors: "#64748b", fontSize: '11px', fontFamily: 'Inter, sans-serif' },
                  formatter: (val) => {
                      if (val >= 1000000) return "₱" + (val / 1000000).toFixed(1) + 'M';
                      if (val >= 1000) return "₱" + (val / 1000).toFixed(1) + 'k';
                      return "₱" + val;
                  }
              }
          },
          grid: {
              show: true,
              borderColor: '#e2e8f0',
              strokeDashArray: 5,
              padding: { left: 0, right: 0, top: 0, bottom: 0 }
          },
          legend: {
              position: 'top',
              horizontalAlign: 'right', 
          },
          tooltip: {
              enabled: true,
              shared: true,
              intersect: false,
              theme: 'light',
              y: {
                  formatter: function(val) {
                      return "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                  }
              }
          }
      };

      budgetChartInstance = new ApexCharts(document.querySelector("#d-budget"), options);
      budgetChartInstance.render();
      
      // Calculate and update total display
      const totalIncome1 = incomeData1.reduce((a, b) => a + b, 0);
      const totalExpense1 = expenseData1.reduce((a, b) => a + b, 0);
      const total1 = totalIncome1 + totalExpense1;
      let total2 = 0;
      
      if (y2Data) {
          const incomeData2 = y2Data.budget ? y2Data.budget.INCOME : [];
          const expenseData2 = y2Data.budget ? y2Data.budget.EXPENSE : [];
          const totalIncome2 = incomeData2.reduce((a, b) => a + b, 0);
          const totalExpense2 = expenseData2.reduce((a, b) => a + b, 0);
          total2 = totalIncome2 + totalExpense2;
      }
      
      const totalText = y2Data ? `Total: ₱${total1.toLocaleString()} vs ₱${total2.toLocaleString()}` : `Total: ₱${total1.toLocaleString()}`;
      
      // Remove total annotation for bar chart
      budgetChartInstance.updateOptions({ annotations: { points: [] } });
  }

  // Net Income Chart Logic
  let netIncomeChartInstance = null;
  function loadNetIncomeChart(y1Data, y2Data, year1, year2) {
      if (!document.querySelector("#d-net-income")) return;

      const incomeData1 = y1Data.income || [];
      const titleEl = document.querySelector("#title-net-income");
      if(titleEl) {
           let titleHtml = `NET INCOME TREND`;
           
           titleEl.innerHTML = titleHtml;
      }

      let series = [];
      if (!y2Data) {
          series = [{
              name: `${year1} Net Income`,
              data: incomeData1
          }];
      } else {
          const incomeData2 = y2Data.income || [];
          series = [{
              name: `${year1} Net Income`,
              data: incomeData1
          }, {
              name: `${year2} Net Income`,
              data: incomeData2
          }];
      }

      if (netIncomeChartInstance) {
          netIncomeChartInstance.destroy();
      }

      const options = {
          series: series,
          chart: {
              fontFamily: '"Inter", sans-serif',
              height: 250,
              type: 'area', // Changed to area chart for gradient fill
              toolbar: { show: false },
              sparkline: { enabled: false },
              animations: {
                  enabled: true,
                  easing: 'easeinout',
                  speed: 800,
                  animateGradually: { enabled: true, delay: 150 },
                  dynamicAnimation: { enabled: true, speed: 350 }
              },
          },
          // Colors: Year 1 Blue, Year 2 Cyan
          colors: !y2Data ? ["#3a57e8"] : ["#3a57e8", "#06b6d4"],
          dataLabels: { enabled: false },
          stroke: {
              curve: 'smooth', // Smooth curves
              width: 3, // Thicker lines
          },
          // Add total display
          annotations: {
              points: [
                  {
                      x: 5,
                      y: 0,
                      marker: {
                          size: 0
                      },
                      label: {
                          text: 'Total: ₱0',
                          textAnchor: 'middle',
                          offsetY: -10,
                          style: {
                              fontSize: '14px',
                              fontWeight: '600',
                              color: '#2c3e50'
                          }
                      }
                  }
              ]
          },
          markers: {
              size: 5, // Larger dots
              strokeColors: '#fff',
              strokeWidth: 3,
              hover: {
                  size: 7,
              }
          },
          xaxis: {
              categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
              labels: {
                  style: { colors: "#64748b", fontSize: '11px', fontFamily: 'Inter, sans-serif' }
              },
              axisBorder: { show: false },
              axisTicks: { show: false }
          },
          yaxis: {
              show: true,
              labels: {
                  style: { colors: "#64748b", fontSize: '11px', fontFamily: 'Inter, sans-serif' },
                  formatter: (val) => {
                      if (val >= 1000000) return "₱" + (val / 1000000).toFixed(1) + 'M';
                      if (val >= 1000) return "₱" + (val / 1000).toFixed(1) + 'k';
                      return "₱" + val;
                  }
              }
          },
          grid: {
              show: true,
              borderColor: '#e2e8f0',
              strokeDashArray: 5,
              padding: { left: 0, right: 0, top: 0, bottom: 0 }
          },
          fill: {
              type: 'gradient',
              gradient: {
                  shade: 'light',
                  type: "vertical",
                  shadeIntensity: 0.5,
                  inverseColors: false,
                  opacityFrom: 0.5,
                  opacityTo: 0.05,
                  stops: [0, 100]
              }
          },
          legend: { 
              show: true, // Enable legend to show Year 1 and Year 2 labels
              position: 'top',
              horizontalAlign: 'center', 
              verticalAlign: 'middle',
              offsetX: 0,
              offsetY: 0,
              fontFamily: 'Inter, sans-serif',
              fontSize: '12px',
              markers: { radius: 12 },
              itemMargin: { horizontal: 10, vertical: 5 }
          },
          tooltip: {
              enabled: true,
              shared: true,
              intersect: false,
              theme: 'light',
              y: {
                  formatter: function(val) {
                      return "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                  }
              }
          },
          annotations: {
              yaxis: [
                  {
                      y: 0,
                      borderColor: '#2c3e50',
                      borderWidth: 1,
                      strokeDashArray: 0,
                      opacity: 0.5
                  }
              ]
          }
      };

      netIncomeChartInstance = new ApexCharts(document.querySelector("#d-net-income"), options);
      netIncomeChartInstance.render();
      
      // Calculate and update total display
      const total1 = incomeData1.reduce((a, b) => a + b, 0);
      let total2 = 0;
      
      if (y2Data) {
          const incomeData2 = y2Data.income || [];
          total2 = incomeData2.reduce((a, b) => a + b, 0);
      }
      
      const totalText = y2Data ? `Total: ₱${total1.toLocaleString()} vs ₱${total2.toLocaleString()}` : `Total: ₱${total1.toLocaleString()}`;
      
      netIncomeChartInstance.updateOptions({
          annotations: {
              points: [{
                  x: 5,
                  y: Math.max(total1, total2) * 1.1,
                  marker: { size: 0 },
                  label: {
                      text: totalText,
                      textAnchor: 'middle',
                      offsetY: -10,
                      style: {
                          fontSize: '14px',
                          fontWeight: '600',
                          color: '#2c3e50'
                      }
                  }
              }]
          }
      });
  }
  
  
  // Inventory Chart Logic
  let invChartInstance = null;
  function loadInventoryChart(year1, year2 = null) {
    if (!document.querySelector("#d-inventory")) return;

    // Use AJAX to get the monthly data
    const requests = [];
    
    // Get inventory data
    requests.push($.ajax({
        url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
        type: "POST",
        data: { action: "GetInventoryChartData", year: year1 },
        dataType: "JSON",
        error: function(xhr, status, error) {
            console.error("GetInventoryChartData AJAX failed:", {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
        }
    }));
    
    // Get markup data
    requests.push($.ajax({
        url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
        type: "POST",
        data: { action: "GetSalesMarkupData", year: year1 },
        dataType: "JSON",
        error: function(xhr, status, error) {
            console.error("GetSalesMarkupData AJAX failed:", {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
        }
    }));
    
    if (year2) {
        // Get inventory data for year 2
        requests.push($.ajax({
            url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
            type: "POST",
            data: { action: "GetInventoryChartData", year: year2 },
            dataType: "JSON",
            error: function(xhr, status, error) {
                console.error("GetInventoryChartData Year2 AJAX failed:", {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
            }
        }));
        
        // Get markup data for year 2
        requests.push($.ajax({
            url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
            type: "POST",
            data: { action: "GetSalesMarkupData", year: year2 },
            dataType: "JSON",
            error: function(xhr, status, error) {
                console.error("GetSalesMarkupData Year2 AJAX failed:", {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
            }
        }));
    }
    
    Promise.all(requests).then(responses => {
        console.log("=== RAW AJAX RESPONSES ===");
        console.log("Response 0 (GetInventoryChartData):", responses[0]);
        console.log("Response 1 (GetSalesMarkupData):", responses[1]);
        console.log("Response 2 (GetInventoryChartData Year2):", responses[2]);
        console.log("Response 3 (GetSalesMarkupData Year2):", responses[3]);
        
        const invRes1 = responses[0];
        const markupRes1 = responses[1];
        const invRes2 = responses[2];
        const markupRes2 = responses[3];
        
        console.log("=== PROCESSED RESPONSES ===");
        console.log("invRes1:", invRes1);
        console.log("markupRes1:", markupRes1);
        console.log("invRes2:", invRes2);
        console.log("markupRes2:", markupRes2);
        
        // Check if inventory data exists and get actual year used
        let cost1 = [], srp1 = [], markup1 = [];
        
        if (invRes1 && invRes1.COST && invRes1.SRP) {
            const actualYear1 = invRes1.YEAR || year1;
            console.log(`Inventory data found for year: ${actualYear1} (requested: ${year1})`);
            
            cost1 = invRes1.COST || [];
            srp1 = invRes1.SRP || [];
            markup1 = invRes1.MARKUP || [];
            
            console.log("Inventory Year 1 data:", { cost: cost1, srp: srp1, markup: markup1 });
            console.log("cost1 sample values:", cost1.slice(0, 3));
            console.log("srp1 sample values:", srp1.slice(0, 3));
            console.log("markup1 sample values:", markup1.slice(0, 3));
            
            // Debug: Log the data structure
            console.log("Inventory Data Structure:", {
                invRes1: invRes1,
                markupRes1: markupRes1,
                cost1: cost1,
                srp1: srp1,
                markup1: markup1,
                cost1Type: typeof cost1,
                cost1IsArray: Array.isArray(cost1),
                srp1Type: typeof srp1,
                srp1IsArray: Array.isArray(srp1),
                markup1Type: typeof markup1,
                markup1IsArray: Array.isArray(markup1),
                dataType: markupRes1?.DATA_TYPE
            });
        } else {
            console.error("❌ Invalid or missing inventory data structure");
            console.log("invRes1:", invRes1);
        }
        
        // Helper function to safely sum array values
        const safeSum = (arr) => {
            if (!Array.isArray(arr) || arr.length === 0) return 0;
            return arr.reduce((a, b) => a + (isNaN(parseFloat(b)) ? 0 : parseFloat(b)), 0);
        };
        
        const totalMarkup1 = safeSum(markup1);  // Use new markup data
        
        // Define series at broader scope to avoid ReferenceError
        let series = [];
        
        // Always use monthly categories (Jan-Dec) for inventory chart
        const xAxisCategories = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        
        console.log("=== INVENTORY CHART DEBUG ===");
        console.log("Year 1:", year1, "Year 2:", year2);
        console.log("Cost data Year 1:", cost1);
        console.log("SRP data Year 1:", srp1);
        
        // Ensure all data arrays have exactly 12 elements (one for each month)
        const normalizeArray = (arr) => {
            if (!Array.isArray(arr)) return new Array(12).fill(0);
            if (arr.length < 12) return [...arr, ...new Array(12 - arr.length).fill(0)];
            if (arr.length > 12) return arr.slice(0, 12);
            return arr.map(val => isNaN(parseFloat(val)) ? 0 : parseFloat(val));
        };
        
        const normalizedCost1 = normalizeArray(cost1);
        const normalizedSrp1 = normalizeArray(srp1);
        
        // Calculate markup: Markup = SRP - Cost
        const calculatedMarkup1 = normalizedSrp1.map((srp, index) => srp - normalizedCost1[index]);
        
        if (year2 && invRes2) {
            // Comparison View - show both years in single chart
            const cost2 = Array.isArray(invRes2?.COST) ? invRes2.COST : [];
            const srp2 = Array.isArray(invRes2?.SRP) ? invRes2.SRP : [];
            
            const normalizedCost2 = normalizeArray(cost2);
            const normalizedSrp2 = normalizeArray(srp2);
            
            // Calculate markup for year 2
            const calculatedMarkup2 = normalizedSrp2.map((srp, index) => srp - normalizedCost2[index]);
            
            console.log("Cost data Year 2:", normalizedCost2);
            console.log("SRP data Year 2:", normalizedSrp2);
            console.log("Calculated Markup Year 2:", calculatedMarkup2);
            
            series = [
                // Year 1 data
                {
                    name: `${year1} January Cost`,
                    type: 'line',
                    data: normalizedCost1
                }, {
                    name: `${year1} January SRP`,
                    type: 'line',
                    data: normalizedSrp1
                }, {
                    name: `${year1} January Markup`,
                    type: 'line',
                    data: calculatedMarkup1
                },
                // Year 2 data
                {
                    name: `${year2} January Cost`,
                    type: 'line',
                    data: normalizedCost2
                }, {
                    name: `${year2} January SRP`,
                    type: 'line',
                    data: normalizedSrp2
                }, {
                    name: `${year2} January Markup`,
                    type: 'line',
                    data: calculatedMarkup2
                }
            ];
            
            console.log("=== COMPARISON CHART SERIES ===");
        } else {
            // Single Year View
            series = [{
                name: `${year1} January Cost`,
                type: 'line',
                data: normalizedCost1
            }, {
                name: `${year1} January SRP`,
                type: 'line',
                data: normalizedSrp1
            }, {
                name: `${year1} January Markup`,
                type: 'line',
                data: calculatedMarkup1
            }];
            
            console.log("=== SINGLE YEAR CHART SERIES ===");
        }
        
        const options = {
            series: series,
            chart: {
                fontFamily: '"Inter", sans-serif',
                height: 250,
                type: 'line', // Pure line chart
                toolbar: { show: false },
                sparkline: { enabled: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: { enabled: true, delay: 150 },
                    dynamicAnimation: { enabled: true, speed: 350 }
                },
            },
            grid: { padding: { left: 0, right: 0, top: 0, bottom: 0 } },
            colors: !invRes2 ? 
                ["#4f46e5", "#10b981", "#f59e0b"] :  // Single year: Cost, SRP, Markup
                ["#4f46e5", "#10b981", "#f59e0b", "#06b6d4", "#8b5cf6", "#ec4899"], // Comparison: Year1 Cost, SRP, Markup, Year2 Cost, SRP, Markup 
            stroke: {
                show: true,
                width: 2,
                curve: 'smooth'
            },
            markers: {
                size: 4,
                hover: {
                    size: 6
                }
            },
            xaxis: {
                categories: xAxisCategories,
                labels: {
                    rotate: 0,  // No rotation for month labels
                    style: { 
                        colors: "#64748b", 
                        fontSize: '12px',  // Standard font size for months
                        fontFamily: 'Inter, sans-serif', 
                        fontWeight: 600 
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                show: true,
                labels: {
                    style: { colors: "#64748b", fontSize: '11px', fontFamily: 'Inter, sans-serif' },
                    formatter: (val) => {
                        if (val >= 1000000) return "₱" + (val / 1000000).toFixed(1) + 'M';
                        if (val >= 1000) return "₱" + (val / 1000).toFixed(1) + 'k';
                        return "₱" + val;
                    }
                }
            },
            legend: { 
                show: true, // Enable legend to show Year 1 and Year 2 labels
                position: 'top',
                horizontalAlign: 'center', 
                verticalAlign: 'middle',
                offsetX: 0,
                offsetY: 0,
                fontFamily: 'Inter, sans-serif',
                fontSize: '12px',
                markers: {
                    radius: 12,
                },
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            }, 
            tooltip: { 
                enabled: true,
                shared: true,
                intersect: false,
                theme: 'light',
                y: {
                    formatter: function(val) {
                        return "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            },
        };
        
        console.log("=== APEXCHARTS OPTIONS ===");
        console.log("Final series being passed to ApexCharts:", series);
        console.log("Chart options:", options);
        console.log("Number of series:", series.length);
        
        // Destroy existing chart instance if it exists
        if (invChartInstance) {
            try {
                invChartInstance.destroy();
            } catch (e) {
                console.warn("Error destroying existing chart instance:", e);
            }
            invChartInstance = null;
        }
        
        // Check if the DOM element exists
        const chartElement = document.querySelector("#d-inventory");
        if (!chartElement) {
            console.error("Chart element #d-inventory not found in DOM");
            return;
        }
        // Clear any previous chart DOM to prevent duplicate canvases on refresh
        chartElement.innerHTML = '';
        
        try {
            invChartInstance = new ApexCharts(chartElement, options);
            invChartInstance.render();
            
            console.log("=== CHART RENDERED ===");
            console.log("Chart instance created successfully");
        } catch (error) {
            console.error("Error rendering chart:", error);
            console.error("Chart element:", chartElement);
            console.error("Chart options:", options);
        }
    }).catch(error => {
        console.error("Inventory Chart data fetch failed:", error);
        console.error("Error details:", {
            message: error.message,
            status: error.status,
            readyState: error.readyState,
            responseText: error.responseText
        });
        
        // Try to identify if it's a network vs server error
        if (error.status === 0) {
            console.error("Network error - check if server is running");
        } else if (error.status >= 500) {
            console.error("Server error - check PHP error logs");
        } else if (error.status === 404) {
            console.error("Endpoint not found - check routes/dashboard.route.php");
        } else {
            console.error("Other error - status:", error.status);
        }
    });
  }

  // Top 5 Sales Products Pie Chart Logic
  let topSalesChartInstance = null;
  function loadTopSalesChart(year1) {
    if (!document.querySelector("#d-top-sales")) return;

    // Use AJAX to get the top sales data
    const requests = [];
    
    // Get top sales data for year 1 - request quantity instead of price
    requests.push($.ajax({
        url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
        type: "POST",
        data: { action: "GetTopSalesData", year: year1, limit: 5, type: "quantity" },
        dataType: "JSON"
    }));
    
    // Get total sales for the entire year
    requests.push($.ajax({
        url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
        type: "POST",
        data: { action: "GetTotalSalesData", year: year1 },
        dataType: "JSON",
        success: function(response) {
            console.log("✅ GetTotalSalesData SUCCESS: Raw response:", response);
            console.log("✅ GetTotalSalesData SUCCESS: Response type:", typeof response);
            console.log("✅ GetTotalSalesData SUCCESS: Response keys:", response ? Object.keys(response) : 'null');
        },
        error: function(xhr, status, error) {
            console.error("❌ GetTotalSalesData AJAX failed:", {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
        }
    }));
    
    console.log("🚀 About to make AJAX calls. Year:", year1);
    console.log("🚀 Total requests to be made:", requests.length);
    
    Promise.all(requests).then(responses => {
        console.log("=== PROMISE.ALL RESPONSES DEBUG ===");
        console.log("Number of responses:", responses.length);
        console.log("Response 0 (TopSales):", responses[0]);
        console.log("Response 1 (TotalProducts):", responses[1]);
        
        const salesRes1 = responses[0];
        const totalSalesRes = responses[1];
        
        console.log("=== TOP SALES DEBUG ===");
        console.log("Raw response:", salesRes1);
        console.log("Products:", salesRes1.PRODUCTS);
        console.log("Sales:", salesRes1.SALES);
        console.log("Total sales response:", totalSalesRes);
        console.log("Total sales response type:", typeof totalSalesRes);
        console.log("Total sales response keys:", totalSalesRes ? Object.keys(totalSalesRes) : 'null');
        
        // Extract total quantity sold for the year
        let totalQuantitySold = 0;
        if (totalSalesRes && totalSalesRes.total_quantity_sold !== undefined) {
            totalQuantitySold = parseInt(totalSalesRes.total_quantity_sold) || 0;
            console.log("✅ SUCCESS: Found total_quantity_sold:", totalSalesRes.total_quantity_sold);
            console.log("✅ SUCCESS: Parsed totalQuantitySold:", totalQuantitySold);
        } else {
            console.log("❌ ERROR: total_quantity_sold not found in response");
            console.log("❌ ERROR: Using 0 as fallback");
            totalQuantitySold = 0;
        }
        console.log("🔍 FINAL: Total quantity sold to display:", totalQuantitySold);
        
        const titleEl = document.querySelector("#title-top-sales");
        if(titleEl) {
             titleEl.innerHTML = `TOP 5 SALES PRODUCTS`;
        }
        
        if (topSalesChartInstance) {
            topSalesChartInstance.destroy();
            topSalesChartInstance = null;
        }
        
        document.querySelector("#d-top-sales").innerHTML = "";

        // Create pie chart data with enhanced validation
        const products = salesRes1.PRODUCTS || [];
        const sales = salesRes1.SALES || [];
        
        console.log("Products array:", products);
        console.log("Sales array:", sales);
        
        // Check if we have valid data
        if (!Array.isArray(products) || !Array.isArray(sales) || products.length === 0 || sales.length === 0) {
            document.querySelector("#d-top-sales").innerHTML = '<div class="text-center text-muted py-5">No sales data available for this period</div>';
            return;
        }
        
        // Filter out invalid data and ensure we have valid numbers
        const validData = sales.map((value, index) => {
            const numValue = parseFloat(value);
            const productName = products[index] || `Product ${index + 1}`;
            
            console.log(`Processing item ${index}: ${value} -> ${numValue}`);
            
            // Additional client-side filtering to remove cancelled items
            if (productName && (
                productName.toString().toLowerCase().includes('cancel') ||
                productName.toString().toLowerCase() === 'cancelled' ||
                productName.toString().toLowerCase() === 'cancel' ||
                productName.toString().trim() === '' ||
                productName.toString().toLowerCase().includes('null')
            )) {
                console.log(`Filtering out cancelled/invalid product: ${productName}`);
                return null; // Filter out this item
            }
            
            return {
                name: productName,
                y: (numValue && !isNaN(numValue) && numValue > 0) ? numValue : 0
            };
        }).filter(item => item && item.y > 0); // Only include items with valid positive values
        
        console.log("Valid data after filtering:", validData);
        
        // If no valid data, show a message
        if (validData.length === 0) {
            document.querySelector("#d-top-sales").innerHTML = '<div class="text-center text-muted py-5">No valid sales data available for this period</div>';
            return;
        }
        
        const series = validData;

        // Final validation before creating chart
        const hasValidSeries = series.every(item => 
            item && 
            typeof item.name === 'string' && 
            typeof item.y === 'number' && 
            !isNaN(item.y) && 
            item.y > 0
        );
        
        if (!hasValidSeries) {
            console.error("Invalid series data detected:", series);
            document.querySelector("#d-top-sales").innerHTML = '<div class="text-center text-muted py-5">Invalid sales data format</div>';
            return;
        }

        console.log("Final series data:", series);

        // One final safety check - ensure no NaN values exist
        const cleanSeries = series.map(item => ({
            name: item.name || 'Unknown',
            y: (typeof item.y === 'number' && !isNaN(item.y) && item.y > 0) ? item.y : 0
        })).filter(item => item.y > 0);

        console.log("Clean series for chart:", cleanSeries);

        if (cleanSeries.length === 0) {
            document.querySelector("#d-top-sales").innerHTML = '<div class="text-center text-muted py-5">No valid sales data available for this period</div>';
            return;
        }

        const options = {
            series: cleanSeries,
            chart: {
                fontFamily: '"Inter", sans-serif',
                height: 500,
                type: 'pie',
                toolbar: { show: false }
            },
            labels: cleanSeries.map(item => item.name),
            colors: ['#3a57e8', '#06b6d4', '#3a57e8', '#06b6d4', '#3a57e8', '#06b6d4'],
            plotOptions: {
                pie: {
                    expandOnClick: false,
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total product sales',
                                formatter: function (w) {
                                    console.log("🔍 FORMATTER DEBUG: totalYearSales value:", totalYearSales);
                                    console.log("🔍 FORMATTER DEBUG: totalYearSales type:", typeof totalYearSales);
                                    console.log("🔍 FORMATTER DEBUG: isNaN check:", isNaN(totalYearSales));
                                    // Always use total year sales, not sum of top 5
                                    return isNaN(totalYearSales) ? "₱0.00" : "₱" + totalYearSales.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    const numVal = parseFloat(val);
                    if (isNaN(numVal)) return "Invalid";
                    return opts.w.config.labels[opts.dataPointIndex] + ": ₱" + numVal.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        const numVal = parseFloat(val);
                        if (isNaN(numVal)) return "₱0.00";
                        return "₱" + numVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            },
            legend: {
                position: 'bottom'
            }
        };

        try {
            // Temporarily disable chart rendering to debug data
            console.log("=== CHART DEBUG INFO ===");
            console.log("Clean series:", cleanSeries);
            console.log("Labels:", cleanSeries.map(item => item.name));
            console.log("Values:", cleanSeries.map(item => item.y));
            console.log("Series length:", cleanSeries.length);
            
            // Check for any remaining issues
            const hasIssues = cleanSeries.some(item => {
                console.log(`Checking item: name="${item.name}", y=${item.y}, typeof y=${typeof item.y}, isNaN=${isNaN(item.y)}`);
                return !item.name || typeof item.y !== 'number' || isNaN(item.y) || item.y <= 0;
            });
            
            if (hasIssues) {
                console.error("Still has issues in clean series!");
                document.querySelector("#d-top-sales").innerHTML = '<div class="text-center text-muted py-5">Data validation failed</div>';
                return;
            }
            
            // FINAL SAFETY CHECK - Double check all values one more time
            const finalSeries = cleanSeries.map(item => ({
                name: String(item.name || 'Unknown'),
                y: Number(item.y) || 0
            })).filter(item => !isNaN(item.y) && item.y > 0);
            
            console.log("FINAL SERIES BEFORE CHART:", finalSeries);
            
            if (finalSeries.length === 0) {
                document.querySelector("#d-top-sales").innerHTML = '<div class="text-center text-muted py-5">No valid data for chart</div>';
                return;
            }
            
            // Create donut chart with click interaction like Clients chart
            const chartOptions = {
                series: finalSeries.map(item => item.y),
                chart: {
                    type: 'donut',
                    height: 360
                },
                labels: finalSeries.map(item => item.name),
                colors: ['#312e81', '#4f46e5', '#8b5cf6', '#c4b5fd', '#e0e7ff'],
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                },
                plotOptions: {
                    pie: {
                        expandOnClick: true,
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Products Sold',
                                    fontSize: '14px',
                                    fontFamily: '"Inter", sans-serif',
                                    fontWeight: 600,
                                    color: '#64748b',
                                    formatter: function (w) {
                                        // Display total quantity sold instead of sum of top 5
                                        return totalQuantitySold.toLocaleString('en-US');
                                    }
                                },
                                value: {
                                    show: true,
                                    fontSize: '24px',
                                    fontFamily: 'Inter, sans-serif',
                                    fontWeight: 700,
                                    color: '#111827',
                                    offsetY: 8,
                                    formatter: function (val) {
                                        return val.toLocaleString('en-US');
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return `${val.toLocaleString('en-US')} times sold`;
                        }
                    }
                }
            };
            
            console.log("=== CREATING DONUT CHART WITH ALTERNATIVE DATA FORMAT ===");
            console.log("Chart options:", chartOptions);
            
            topSalesChartInstance = new ApexCharts(document.querySelector("#d-top-sales"), chartOptions);
            topSalesChartInstance.render();
            
            // Add click handler for pie segments after chart is rendered
            setTimeout(() => {
                const chartEl = document.querySelector("#d-top-sales");
                if (!chartEl) return;
                
                const pieSlices = chartEl.querySelectorAll('.apexcharts-pie-slice');
                
                if (pieSlices && pieSlices.length > 0) {
                    pieSlices.forEach((slice, index) => {
                        slice.style.cursor = 'pointer';
                        slice.addEventListener('click', function(e) {
                            e.stopPropagation();
                            console.log("Pie slice clicked:", index);
                            
                            // Manually trigger the selection
                            topSalesChartInstance.toggleDataPointSelection(index);
                        });
                    });
                }
            }, 1000);
        } catch (error) {
            console.error("Chart rendering failed:", error);
            document.querySelector("#d-top-sales").innerHTML = '<div class="text-center text-muted py-5">Unable to render sales chart</div>';
        }
    }).catch(error => {
        console.error("Top Sales Chart data fetch failed:", error);
    });
  }

  // Income Breakdown Chart Logic
  let incomeBreakdownChartInstance = null;
  function loadIncomeBreakdownChart(year1, year2 = null) {
    console.log("=== LOAD INCOME BREAKDOWN CHART ===");
    console.log("Year1:", year1, "Year2:", year2);
    
    const chartContainer = document.querySelector("#d-income-breakdown");
    if (!chartContainer) {
        console.error("❌ Income breakdown chart container not found");
        return;
    }
    
    console.log("✅ Chart container found");

    // Destroy existing chart instance first
    if (incomeBreakdownChartInstance) {
        try {
            incomeBreakdownChartInstance.destroy();
            incomeBreakdownChartInstance = null;
            console.log("✅ Destroyed existing chart instance");
        } catch (error) {
            console.error("Error destroying chart:", error);
            incomeBreakdownChartInstance = null;
        }
    }

    // Clear and reset container
    chartContainer.innerHTML = '';
    chartContainer.style.display = 'block';
    chartContainer.style.visibility = 'visible';

    // Use AJAX to get the monthly data
    const requests = [];
    requests.push($.ajax({
        url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
        type: "POST",
        data: { action: "GetIncomeBreakdownData", year: year1 },
        dataType: "JSON"
    }));
    
    if (year2) {
        requests.push($.ajax({
            url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
            type: "POST",
            data: { action: "GetIncomeBreakdownData", year: year2 },
            dataType: "JSON"
        }));
    }
    
    Promise.all(requests).then(responses => {
        console.log("=== INCOME BREAKDOWN API RESPONSES ===");
        console.log("Response 1:", responses[0]);
        if (responses[1]) console.log("Response 2:", responses[1]);
        
        const res1 = responses[0];
        const res2 = responses[1] || null;
        
        // Validate response data
        if (!res1 || !res1.MERCHANDISE || !res1.SERVICE || !res1.OTHER) {
            console.error("❌ Invalid response data structure");
            chartContainer.innerHTML = '<div class="text-center text-muted py-5">No data available</div>';
            return;
        }
        
        // Aggregate monthly data into annual totals
        const merch1 = (res1.MERCHANDISE || []).reduce((a, b) => a + b, 0);
        const serv1 = (res1.SERVICE || []).reduce((a, b) => a + b, 0);
        const other1 = (res1.OTHER || []).reduce((a, b) => a + b, 0);
        
        console.log("=== CALCULATED TOTALS ===");
        console.log("Merch1:", merch1, "Serv1:", serv1, "Other1:", other1);
        
        // Check if we have any data
        if (merch1 === 0 && serv1 === 0 && other1 === 0) {
            console.log("⚠️ No data to display");
            chartContainer.innerHTML = '<div class="text-center text-muted py-5">No income breakdown data available</div>';
            return;
        }
        
        // Side-by-side layout: 3 columns for each income type
        const categories = ['Merchandise', 'Service', 'Other'];
        let year1Data = [merch1, serv1, other1];
        let year2Data = [0, 0, 0];
        
        // Declare variables outside if block for function scope
        let merch2 = 0, serv2 = 0, other2 = 0;

        if (res2) {
            merch2 = (res2.MERCHANDISE || []).reduce((a, b) => a + b, 0);
            serv2 = (res2.SERVICE || []).reduce((a, b) => a + b, 0);
            other2 = (res2.OTHER || []).reduce((a, b) => a + b, 0);
            
            year2Data = [merch2, serv2, other2];
        }

        const titleEl = document.querySelector("#title-income-breakdown");
        if (titleEl) {
            titleEl.innerHTML = `Income Breakdown`;
        }

        const options = {
            series: [{
                name: 'Year ' + year1 + ' January',
                data: year1Data
            }],
            chart: {
                type: 'bar',
                height: 230,
                fontFamily: '"Inter", sans-serif',
                toolbar: {
                    show: false
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: { enabled: true, delay: 150 },
                    dynamicAnimation: { enabled: true, speed: 350 }
                }
            },
            colors: ['#3a57e8'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '70%',
                    borderRadius: 4,
                    dataLabels: {
                        position: 'top',
                    }
                }
            },
            dataLabels: {
                enabled: false,
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ["#304758"]
                },
                formatter: function (val) {
                    return "₱" + val.toLocaleString('en-US', {maximumFractionDigits: 0});
                }
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: categories,
                position: 'bottom',
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                crosshairs: {
                    fill: {
                        type: 'gradient',
                        gradient: {
                            colorFrom: '#D8E3F0',
                            colorTo: '#BED1E6',
                            stops: [0, 100],
                            opacityFrom: 0.4,
                            opacityTo: 0.5,
                        }
                    }
                },
                tooltip: {
                    enabled: true,
                    offsetY: -35
                }
            },
            yaxis: {
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false,
                },
                labels: {
                    show: true,
                    formatter: function (val) {
                        return "₱" + val.toLocaleString('en-US', {maximumFractionDigits: 0});
                    }
                }
            },
            fill: {
                opacity: 1
            },
            legend: {
                show: false
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "₱" + val.toLocaleString('en-US', {maximumFractionDigits: 2});
                    }
                }
            }
        };

        // Add Year 2 series if available
        if (year2 && res2) {
            console.log("=== ADDING YEAR 2 SERIES ===");
            console.log("Year2:", year2);
            console.log("Res2 data:", res2);
            console.log("Year 2 totals - Merch:", merch2, "Serv:", serv2, "Other:", other2);
            
            options.series.push({
                name: 'Year ' + year2 + ' January',
                data: year2Data
            });
            options.colors = ['#3a57e8', '#06b6d4'];
            console.log("Updated series:", options.series);
            console.log("Updated colors:", options.colors);
        } else {
            console.log("=== NO YEAR 2 DATA ===");
            console.log("Year2:", year2);
            console.log("Res2:", res2);
        }

        console.log("=== CREATING CHART ===");
        console.log("Chart options:", options);
        
        try {
            // Ensure container is ready
            if (!chartContainer || chartContainer.nodeType !== 1) {
                throw new Error("Chart container is not a valid DOM element");
            }
            
            incomeBreakdownChartInstance = new ApexCharts(chartContainer, options);
            incomeBreakdownChartInstance.render();
            console.log("✅ Income breakdown chart rendered successfully");
            
            // Calculate and update total display
            const total1 = merch1 + serv1 + other1;
            const total2 = res2 ? (merch2 + serv2 + other2) : 0;
            const totalText = res2 ? `Total: ₱${total1.toLocaleString()} vs ₱${total2.toLocaleString()}` : `Total: ₱${total1.toLocaleString()}`;
            
            // Remove total annotation for bar chart
            incomeBreakdownChartInstance.updateOptions({ annotations: { points: [] } });
        } catch (error) {
            console.error("❌ Error rendering income breakdown chart:", error);
            console.error("Error details:", error.message, error.stack);
            
            // Fallback display
            chartContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="mb-3">
                        <strong>Income Breakdown (${year1})</strong>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Merchandise:</strong><br>
                            ₱${merch1.toLocaleString()}
                        </div>
                        <div class="col-md-4">
                            <strong>Service:</strong><br>
                            ₱${serv1.toLocaleString()}
                        </div>
                        <div class="col-md-4">
                            <strong>Other:</strong><br>
                            ₱${other1.toLocaleString()}
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Update Income Breakdown table
        updateIncomeBreakdownTable(year1, merch1, serv1, other1, res2 ? year2 : null, res2 ? merch2 : 0, res2 ? serv2 : 0, res2 ? other2 : 0);
    }).catch(error => {
        console.error("Income Breakdown Chart data fetch failed:", error);
        chartContainer.innerHTML = '<div class="text-center text-muted py-5">Error loading chart data</div>';
    });
  }
  
  // Function to update Income Breakdown Data Table
  function updateIncomeBreakdownTable(year1, merch1, serv1, other1, year2, merch2, serv2, other2) {
      console.log("=== INCOME BREAKDOWN TABLE UPDATE ===");
      console.log("Table update called with:", { year1, merch1, serv1, other1, year2, merch2, serv2, other2 });
      
      // Check if table elements exist
      const tableExists = document.querySelector("#income-breakdown-table");
      if (!tableExists) {
          console.log("Income Breakdown table not found");
          return;
      }
      
      // Update Year 1 data
      const year1MerchEl = document.querySelector("#year1-merchandise");
      const year1ServEl = document.querySelector("#year1-service");
      const year1OtherEl = document.querySelector("#year1-other");
      const year1LabelEl = document.querySelector("#income-breakdown-year1-label");
      
      // Update Year 1 label
      if (year1LabelEl) {
          year1LabelEl.textContent = year1 + ' January';
      }
      
      if (year1MerchEl) {
          year1MerchEl.textContent = "₱" + parseFloat(merch1 || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      }
      if (year1ServEl) {
          year1ServEl.textContent = "₱" + parseFloat(serv1 || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      }
      if (year1OtherEl) {
          year1OtherEl.textContent = "₱" + parseFloat(other1 || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      }
      
      // Update Year 2 data (only if year2 exists)
      if (year2 && merch2 !== undefined && serv2 !== undefined && other2 !== undefined) {
          const year2MerchEl = document.querySelector("#year2-merchandise");
          const year2ServEl = document.querySelector("#year2-service");
          const year2OtherEl = document.querySelector("#year2-other");
          const year2LabelEl = document.querySelector("#income-breakdown-year2-label");
          
          // Update Year 2 label
          if (year2LabelEl) {
              year2LabelEl.textContent = year2 + ' January';
          }
          
          if (year2MerchEl) {
              year2MerchEl.textContent = "₱" + parseFloat(merch2 || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          }
          if (year2ServEl) {
              year2ServEl.textContent = "₱" + parseFloat(serv2 || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          }
          if (year2OtherEl) {
              year2OtherEl.textContent = "₱" + parseFloat(other2 || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          }
      } else {
          // Clear Year 2 data if no comparison year
          const year2MerchEl = document.querySelector("#year2-merchandise");
          const year2ServEl = document.querySelector("#year2-service");
          const year2OtherEl = document.querySelector("#year2-other");
          
          if (year2MerchEl) year2MerchEl.textContent = "₱0.00";
          if (year2ServEl) year2ServEl.textContent = "₱0.00";
          if (year2OtherEl) year2OtherEl.textContent = "₱0.00";
      }
  }

  // Client Type Pie Chart Logic
  function loadClientTypeChart() {
    if (!document.querySelector("#d-client-type")) return;

    $.ajax({
        url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
        type: "POST",
        data: { action: "GetClientTypeData" },
        dataType: "JSON",
        success: function(response) {
            const types = response.types || [];
            const counts = response.counts || [];
            
            const totalClients = counts.reduce((a, b) => a + b, 0);

                const options = {
                series: counts,
                chart: {
                    type: 'donut', // Changed to donut for better center text display
                    height: 380,
                    fontFamily: '"Inter", sans-serif',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: { enabled: true, delay: 150 },
                        dynamicAnimation: { enabled: true, speed: 350 }
                    },
                },
                labels: types,
                colors: ['#312e81', '#4f46e5', '#8b5cf6', '#c4b5fd', '#e0e7ff'],
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Customers',
                                    fontSize: '18px',
                                    fontFamily: '"Inter", sans-serif',
                                    fontWeight: 600,
                                    color: '#64748b',
                                    formatter: function (w) {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return total.toLocaleString('en-US');
                                    }
                                },
                                value: {
                                    show: true,
                                    fontSize: '24px',
                                    fontFamily: '"Inter", sans-serif',
                                    fontWeight: 700,
                                    color: '#111827',
                                    offsetY: 8,
                                    formatter: function (val) {
                                        return val;
                                    }
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontFamily: '"Inter", sans-serif',
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(1) + "%";
                    },
                    style: {
                        fontSize: '12px',
                        fontFamily: '"Inter", sans-serif',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    },
                    dropShadow: { enabled: false }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " Customers";
                        }
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#d-client-type"), options);
            chart.render();
            
            // Calculate and update total display
            const total = counts.reduce((a, b) => a + b, 0);
            
            chart.updateOptions({
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'Total Customers',
                                    formatter: function (w) {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return isNaN(total) ? "₱0.00" : total.toLocaleString('en-US');
                                    }
                                }
                            }
                        }
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            console.error("Client Type Chart data fetch failed:", error);
        }
    });
  }

  // Inventory Sales by Department Chart Logic
  let invSalesDeptChartInstance = null;
  function loadInventorySalesByDepartmentChart(year1, year2 = null) {
    if (!document.querySelector("#d-inv-sales-dept")) return;

    const requests = [];
    requests.push($.ajax({
        url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
        type: "POST",
        data: { action: "GetInventorySalesByDepartmentData", year: year1 },
        dataType: "JSON"
    }));
    
    if (year2) {
        requests.push($.ajax({
            url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
            type: "POST",
            data: { action: "GetInventorySalesByDepartmentData", year: year2 },
            dataType: "JSON"
        }));
    }
    
    Promise.all(requests).then(responses => {
        const res1 = responses[0];
        const res2 = responses[1] || null;
        
        const depts1 = res1.departments || [];
        const sales1 = res1.sales || [];
        
        let allDepts = [...new Set([...depts1, ...(res2 ? (res2.departments || []) : [])])];
        allDepts.sort(); 
        
        const getSalesForDept = (dept, sourceDepts, sourceSales) => {
            const idx = sourceDepts.indexOf(dept);
            return idx !== -1 ? sourceSales[idx] : 0;
        };
        
        const data1 = allDepts.map(d => getSalesForDept(d, depts1, sales1));
        const data2 = res2 ? allDepts.map(d => getSalesForDept(d, res2.departments || [], res2.sales || [])) : [];
        
        const titleEl = document.querySelector("#title-inv-sales-dept");
        if(titleEl) {
             let titleHtml = `Transactions by Client Category (${year1})`;
             if (res2) {
                 titleHtml += ` <small class="text-muted">vs ${year2}</small>`;
             }
             titleEl.innerHTML = titleHtml;
        }
        
        if (invSalesDeptChartInstance) {
            invSalesDeptChartInstance.destroy();
            invSalesDeptChartInstance = null;
        }
        
        document.querySelector("#d-inv-sales-dept").innerHTML = "";

        // Pie charts expect a single series of numbers
        // We will show Year 1 data by default
        let series = data1; 
        
        // If Year 2 is present, we could switch or show year 1 only. 
        // For a Pie chart, showing comparison is complex. 
        // We will stick to the primary selected year (Year 1).

        const options = {
            series: series,
            labels: allDepts, // Categories
        chart: {
                fontFamily: '"Inter", sans-serif',
                height: 400,
                type: 'pie',
                toolbar: { show: false },
                sparkline: { enabled: false },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: { enabled: true, delay: 150 },
                dynamicAnimation: { enabled: true, speed: 350 }
            },
            },
            colors: ["#3a57e8", "#06b6d4", "#3a57e8", "#06b6d4", "#3a57e8", "#06b6d4", "#3a57e8", "#06b6d4"], // Blue/Cyan palette
            dataLabels: { 
                enabled: true,
                formatter: function (val, opt) {
                    // Show percentage
                    return val.toFixed(1) + "%";
                },
                style: {
                    fontSize: '12px',
                    colors: ['#fff'],
                    fontFamily: '"Inter", sans-serif',
                    fontWeight: 'bold',
                },
                dropShadow: { enabled: false }
            },
            stroke: {
                show: true,
                width: 0, // No border for pie slices usually, or thin white
                colors: ['#fff']
            },
            legend: { 
                show: true,
                position: 'bottom',
                horizontalAlign: 'center', 
                fontFamily: 'Inter, sans-serif',
            },
            tooltip: {
                enabled: true,
                y: {
                    formatter: function(val) {
                        return val + " Transactions";
                    }
                }
            }
        };

        invSalesDeptChartInstance = new ApexCharts(chartContainer, options);
        invSalesDeptChartInstance.render();
        
        // Calculate and update total display
        const total1 = data1.reduce((a, b) => a + b, 0);
        const total2 = res2 ? data2.reduce((a, b) => a + b, 0) : 0;
        const totalText = res2 ? `Total: ${total1.toLocaleString()} vs ${total2.toLocaleString()} transactions` : `Total: ${total1.toLocaleString()} transactions`;
        
        invSalesDeptChartInstance.updateOptions({
            annotations: {
                points: [{
                    x: Math.floor(allDepts.length / 2),
                    y: Math.max(...data1, ...data2) * 1.1,
                    marker: { size: 0 },
                    label: {
                        text: totalText,
                        textAnchor: 'middle',
                        offsetY: -10,
                        style: {
                            fontSize: '14px',
                            fontWeight: '600',
                            color: '#2c3e50'
                        }
                    }
                }]
            }
        });
    }).catch(error => {
        console.error("Inventory Sales Dept Chart data fetch failed:", error);
    });
  }

  // Load initial data (current year/date)
  const currentDate = new Date();
  const currentYear = currentDate.getFullYear();
  
  // Year Picker Logic
  if ($("#dashboard-year").length) {
      // Main Year Change
      $("#dashboard-year").on('change', function() {
          const year1 = $(this).val();
          const year2 = $("#dashboard-year-compare").val();
          
          loadChartData(year1, year2);
          loadInventoryChart(year1, year2);
          loadIncomeBreakdownChart(year1, year2);
          loadInventorySalesByDepartmentChart(year1, year2);
          loadTopSalesChart(year1);
          loadPaidUnpaidChart(year1, year2);
          loadIncomeStatementChart(year1, year2);
          
          const dFrom = `01/01/${year1}`;
          const dTo = `12/31/${year1}`;
          loadDashboardStats(dFrom, dTo);
      });

      // Comparison Year Change
      $("#dashboard-year-compare").on('change', function() {
          const year1 = $("#dashboard-year").val();
          const year2 = $(this).val();
          loadChartData(year1, year2);
          loadInventoryChart(year1, year2);
          loadIncomeBreakdownChart(year1, year2);
          loadInventorySalesByDepartmentChart(year1, year2);
          loadTopSalesChart(year1);
          loadPaidUnpaidChart(year1, year2);
          loadIncomeStatementChart(year1, year2);
      });
      
      // Initial Load
      const defaultYear = $("#dashboard-year").val() || currentYear;
      const dFrom = `01/01/${defaultYear}`;
      const dTo = `12/31/${defaultYear}`;
      
      loadChartData(defaultYear, null);
      loadInventoryChart(defaultYear);
      loadIncomeBreakdownChart(defaultYear);
      loadInventorySalesByDepartmentChart(defaultYear);
      loadTopSalesChart(defaultYear);
      
      loadPaidUnpaidChart(defaultYear, null); // Load Paid/Unpaid chart
      loadIncomeStatementChart(defaultYear, null); // Load Income Statement chart
      
      loadClientTypeChart(); // No date filter for clients usually
      loadDashboardStats(dFrom, dTo);
  } else {
      // Fallback if element missing
      loadChartData(currentYear);
      loadInventoryChart(currentYear);
      loadIncomeBreakdownChart(currentYear);
      loadInventorySalesByDepartmentChart(currentYear);
      loadTopSalesChart(currentYear);
      
      loadClientTypeChart();
      loadDashboardStats(); 
      loadPaidUnpaidChart(currentYear, null); // Load Paid/Unpaid chart
      loadIncomeStatementChart(currentYear, null); // Load Income Statement chart
  }

  // Paid/Unpaid Chart Functions
  let paidUnpaidChart = null;
  
  function loadPaidUnpaidChart(year1, year2 = null) {
      if (document.querySelectorAll('#d-paid-unpaid').length === 0) return;
      
      const requestData = { action: "GetPaidUnpaidChartData", year: year1 };
      if (year2) {
          requestData.year2 = year2;
      }
      
      $.ajax({
          url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
          type: "POST",
          data: requestData,
          dataType: "JSON",
          success: function(response) {
              renderPaidUnpaidChart(response);
              updatePaidUnpaidSummary(response);
          },
          error: function(xhr, status, error) {
              console.error("Paid/Unpaid chart data fetch failed:", error);
          }
      });
  }
  
  function updatePaidUnpaidSummary(data) {
      console.log("=== PAID/UNPAID SUMMARY UPDATE ===");
      console.log("Data received:", data);
      console.log("YEAR1:", data.YEAR1);
      console.log("YEAR2:", data.YEAR2);
      console.log("PAID_YEAR1:", data.PAID_YEAR1);
      console.log("UNPAID_YEAR1:", data.UNPAID_YEAR1);
      
      const formatCurrency = (val) => "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      
      // Update Year 1 row
      const year1Label = data.YEAR1 + ' January';
      console.log("Setting Year 1 label to:", year1Label);
      $('#summary-year1-label').text(year1Label);
      $('#total-paid-year1').text(formatCurrency(data.PAID_YEAR1));
      $('#total-unpaid-year1').text(formatCurrency(data.UNPAID_YEAR1));
      
      // Handle Year 2 row if present
      if (data.YEAR2) {
          const year2Label = data.YEAR2 + ' January';
          console.log("Setting Year 2 label to:", year2Label);
          $('#paid-unpaid-year2-row').show();
          $('#summary-year2-label').text(year2Label);
          $('#total-paid-year2').text(formatCurrency(data.PAID_YEAR2));
          $('#total-unpaid-year2').text(formatCurrency(data.UNPAID_YEAR2));
      } else {
          $('#paid-unpaid-year2-row').hide();
      }
  }
  
  function renderPaidUnpaidChart(data) {
      // Group by Paid/Unpaid as categories; show years as side-by-side series
      const categories = ['Paid', 'Unpaid'];
      
      const series = data.YEAR2 ? [
          {
              name: 'Year ' + data.YEAR1 + ' January',
              data: [data.PAID_YEAR1, data.UNPAID_YEAR1]
          },
          {
              name: 'Year ' + data.YEAR2 + ' January',
              data: [data.PAID_YEAR2, data.UNPAID_YEAR2]
          }
      ] : [
          {
              name: 'Year ' + data.YEAR1 + ' January',
              data: [data.PAID_YEAR1, data.UNPAID_YEAR1]
          }
      ];
      
      const options = {
          series: series,
          chart: {
              type: 'bar',
              height: 230,
              fontFamily: '"Inter", sans-serif',
              toolbar: {
                  show: false
              },
              animations: {
                  enabled: true,
                  easing: 'easeinout',
                  speed: 800,
                  animateGradually: { enabled: true, delay: 150 },
                  dynamicAnimation: { enabled: true, speed: 350 }
              }
          },
          colors: data.YEAR2 ? ['#3a57e8', '#06b6d4'] : ['#3a57e8'], // Blue for Year1, Cyan for Year2
          plotOptions: {
              bar: {
                  horizontal: false,
                  columnWidth: data.YEAR2 ? '45%' : '35%',
                  borderRadius: 4
              },
          },
          
          dataLabels: {
              enabled: false,
              formatter: function (val) {
                  return "₱" + parseFloat(val).toLocaleString('en-US', { maximumFractionDigits: 0 });
              },
              style: {
                  fontSize: '12px',
                  fontWeight: 600
              },
              offsetY: -20
          },
          stroke: {
              show: true,
              width: 2,
              colors: ['transparent']
          },
          xaxis: {
              categories: categories,
              labels: {
                  style: {
                      fontSize: '14px',
                      fontWeight: 600
                  }
              }
          },
          yaxis: {
              labels: {
                  formatter: (val) => {
                      if (Math.abs(val) >= 1000000) return "₱" + (val / 1000000).toFixed(1) + 'M';
                      if (Math.abs(val) >= 1000) return "₱" + (val / 1000).toFixed(0) + 'k';
                      return "₱" + val.toFixed(0);
                  }
              }
          },
          fill: {
              opacity: 1
          },
          tooltip: {
              y: {
                  formatter: function (val) {
                      return "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                  }
              }
          },
          legend: {
              show: false,
              position: 'top',
              horizontalAlign: 'center',
              fontSize: '14px',
              fontWeight: 600
          },
          grid: {
              borderColor: '#f1f1f1',
              padding: { left: 0, right: 0, top: 0, bottom: 0 }
          }
      };

      const chartContainer = document.querySelector("#d-paid-unpaid");
      if (chartContainer) {
          chartContainer.innerHTML = '';
      }
      if (paidUnpaidChart) {
          paidUnpaidChart.destroy();
          paidUnpaidChart = null;
      }
      if (chartContainer) {
          paidUnpaidChart = new ApexCharts(chartContainer, options);
          paidUnpaidChart.render();
          
          // Calculate and update total display
          const total1 = data.PAID_YEAR1 + data.UNPAID_YEAR1;
          const total2 = data.YEAR2 ? (data.PAID_YEAR2 + data.UNPAID_YEAR2) : 0;
          const totalText = data.YEAR2 ? `Total: ₱${total1.toLocaleString()} vs ₱${total2.toLocaleString()}` : `Total: ₱${total1.toLocaleString()}`;
          
          paidUnpaidChart.updateOptions({ annotations: { points: [] } });
      }
  }

  // Income Statement Chart Functions
  let incomeStatementChart = null;
  
  function loadIncomeStatementChart(year1, year2 = null) {
      if (document.querySelectorAll('#d-income-statement').length === 0) return;
      
      const requestData = { action: "GetIncomeStatementChartData", year: year1 };
      if (year2) {
          requestData.year2 = year2;
      }
      
      $.ajax({
          url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
          type: "POST",
          data: requestData,
          dataType: "JSON",
          success: function(response) {
              renderIncomeStatementChart(response);
              updateIncomeStatementTable(response);
          },
          error: function(xhr, status, error) {
              console.error("Income Statement chart data fetch failed:", error);
          }
      });
  }
  
  function updateIncomeStatementTable(data) {
      const formatCurrency = (val) => "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      
      // Update Year 1 row
      $('#income-statement-year1-label').text(data.YEAR1 + ' January');
      $('#revenue-year1').text(formatCurrency(data.REVENUE_YEAR1));
      $('#expenses-year1').text(formatCurrency(data.EXPENSES_YEAR1));
      $('#income-before-tax-year1').text(formatCurrency(data.INCOME_BEFORE_TAX_YEAR1));
      $('#provision-year1').text(formatCurrency(data.PROVISION_YEAR1));
      $('#income-after-tax-year1').text(formatCurrency(data.INCOME_AFTER_TAX_YEAR1));
      
      // Handle Year 2 row if present
      if (data.YEAR2) {
          $('#income-statement-year2-row').show();
          $('#income-statement-year2-label').text(data.YEAR2 + ' January');
          $('#revenue-year2').text(formatCurrency(data.REVENUE_YEAR2));
          $('#expenses-year2').text(formatCurrency(data.EXPENSES_YEAR2));
          $('#income-before-tax-year2').text(formatCurrency(data.INCOME_BEFORE_TAX_YEAR2));
          $('#provision-year2').text(formatCurrency(data.PROVISION_YEAR2));
          $('#income-after-tax-year2').text(formatCurrency(data.INCOME_AFTER_TAX_YEAR2));
      } else {
          $('#income-statement-year2-row').hide();
      }
  }
  
  function renderIncomeStatementChart(data) {
      // Build categories as income statement components (without donation)
      const categories = ['Revenue', 'Expenses', 'Net Profit Before Tax', 'Provision For Income Tax', 'Net Income After Tax'];
      
      // Group Year 1 and Year 2 data side by side for each category
      const series = data.YEAR2 ? [
          {
              name: 'Year ' + data.YEAR1 + ' January',
              data: [
                  data.REVENUE_YEAR1,
                  data.EXPENSES_YEAR1,
                  data.INCOME_BEFORE_TAX_YEAR1,
                  data.PROVISION_YEAR1,
                  data.INCOME_AFTER_TAX_YEAR1
              ]
          },
          {
              name: 'Year ' + data.YEAR2 + ' January',
              data: [
                  data.REVENUE_YEAR2,
                  data.EXPENSES_YEAR2,
                  data.INCOME_BEFORE_TAX_YEAR2,
                  data.PROVISION_YEAR2,
                  data.INCOME_AFTER_TAX_YEAR2
              ]
          }
      ] : [
          {
              name: 'Year ' + data.YEAR1 + ' January',
              data: [
                  data.REVENUE_YEAR1,
                  data.EXPENSES_YEAR1,
                  data.INCOME_BEFORE_TAX_YEAR1,
                  data.PROVISION_YEAR1,
                  data.INCOME_AFTER_TAX_YEAR1
              ]
          }
      ];
      
      const options = {
          series: series,
          chart: {
              type: 'bar',
              height: 230,
              fontFamily: '"Inter", sans-serif',
              toolbar: {
                  show: false
              },
              animations: {
                  enabled: true,
                  easing: 'easeinout',
                  speed: 800,
                  animateGradually: { enabled: true, delay: 150 },
                  dynamicAnimation: { enabled: true, speed: 350 }
              }
          },
          colors: data.YEAR2 ? ['#3a57e8', '#06b6d4'] : ['#3a57e8'],
          plotOptions: {
              bar: {
                  horizontal: false,
                  columnWidth: data.YEAR2 ? '45%' : '35%',
                  borderRadius: 4,
                  grouped: true
              },
          },
          
          dataLabels: {
              enabled: false,
              formatter: function (val) {
                  return "₱" + parseFloat(val).toLocaleString('en-US', { maximumFractionDigits: 0 });
              },
              style: {
                  fontSize: '10px',
                  fontWeight: 600
              },
              offsetY: -20
          },
          stroke: {
              show: true,
              width: 2,
              colors: ['transparent']
          },
          xaxis: {
              categories: categories,
              labels: {
                  style: {
                      fontSize: '12px',
                      fontWeight: 600
                  }
              }
          },
          yaxis: {
              labels: {
                  formatter: (val) => {
                      if (Math.abs(val) >= 1000000) return "₱" + (val / 1000000).toFixed(1) + 'M';
                      if (Math.abs(val) >= 1000) return "₱" + (val / 1000).toFixed(0) + 'k';
                      return "₱" + val.toFixed(0);
                  }
              }
          },
          fill: {
              opacity: 1
          },
          tooltip: {
              shared: false,
              intersect: true,
              y: {
                  formatter: function (val) {
                      return "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                  }
              }
          },
          legend: {
              show: false,
              position: 'top',
              horizontalAlign: 'center',
              fontSize: '14px',
              fontWeight: 600
          },
          grid: {
              borderColor: '#f1f1f1',
              padding: { left: 0, right: 0, top: 0, bottom: 0 }
          }
      };

      const chartContainer = document.querySelector("#d-income-statement");
      
      // Destroy existing chart instance
      if (incomeStatementChart) {
          incomeStatementChart.destroy();
      }
      
      
      // Create new chart instance
      incomeStatementChart = new ApexCharts(chartContainer, options);
      incomeStatementChart.render();
      
      // Calculate and update total display
      const total1 = data.REVENUE_YEAR1 + data.EXPENSES_YEAR1 + data.INCOME_BEFORE_TAX_YEAR1 + data.PROVISION_YEAR1 + data.INCOME_AFTER_TAX_YEAR1;
      let total2 = 0;
      
      if (data.YEAR2) {
          total2 = data.REVENUE_YEAR2 + data.EXPENSES_YEAR2 + data.INCOME_BEFORE_TAX_YEAR2 + data.PROVISION_YEAR2 + data.INCOME_AFTER_TAX_YEAR2;
      }
      
      const totalText = data.YEAR2 ? `Total: ₱${total1.toLocaleString()} vs ₱${total2.toLocaleString()}` : `Total: ₱${total1.toLocaleString()}`;
      
      incomeStatementChart.updateOptions({ annotations: { points: [] } });
  }


  // Removed old dropdown listener - now uses Financial Condition year selectors

  document.addEventListener('ColorChange', (e) => {
    console.log(e)
    const newOpt = {
      colors: [e.detail.detail1, e.detail.detail2],
      fill: {
        type: 'gradient',
        gradient: {
            shade: 'dark',
            type: "vertical",
            shadeIntensity: 0,
            gradientToColors: [e.detail.detail1, e.detail.detail2], // optional, if not defined - uses the shades of same color in series
            inverseColors: true,
            opacityFrom: .4,
            opacityTo: .1,
            stops: [0, 50, 60],
            colors: [e.detail.detail1, e.detail.detail2],
        }
    },
   }
    chart.updateOptions(newOpt)
  })

    // Fetch Dashboard Stats for Circular Progress Widgets
    function loadDashboardStats(dateFrom = null, dateTo = null) {
        // If no date provided, use current date formatted appropriately for PHP
        let payload = { action: "GetDashboardStats" };
        
        if (dateFrom && dateTo) {
            payload.dateFrom = dateFrom;
            payload.dateTo = dateTo;
        } else {
            // Fallback to single date or today if not provided (though we try to always provide range now)
            const now = new Date();
            const pad = (n) => n < 10 ? '0' + n : n;
            const dateStr = pad(now.getMonth() + 1) + '/' + pad(now.getDate()) + '/' + now.getFullYear();
            payload.date = dateStr; // Legacy fallback
        }

        $.ajax({
            url: "/iSynApp-main/routes/dashboard/dashboard.route.php",
            type: "POST",
            data: payload,
            dataType: "JSON",
            success: function(response) {
                // Helper to format currency
                const formatCurrency = (val) => "₱" + parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const currentYear = new Date().getFullYear();

                // Revenue
                $('#val-revenue').text(formatCurrency(response.revenue));

                // Expenses
                $('#val-expenses').text(formatCurrency(response.expenses));

                // Income
                $('#val-income').text(formatCurrency(response.income));

                // Accounts Receivable
                $('#val-receivable').text(formatCurrency(response.receivable));

                // Accounts Payable
                $('#val-payable').text(formatCurrency(response.payable));

                // Income Budget
                $('#val-income-budget').text(formatCurrency(response.income_budget));

                // Expenses Budget
                $('#val-expenses-budget').text(formatCurrency(response.expenses_budget));
                
                // Inventory Cost
                $('#val-inv-cost').text(formatCurrency(response.inventory_cost));

                // Inventory SRP
                $('#val-inv-srp').text(formatCurrency(response.inventory_srp));

                // Net Income
                $('#val-net-income').text(formatCurrency(response.income));
                
                // Today's Sales
                $('#val-today').text(formatCurrency(response.today_sales));

                // Members
                $('#val-members').text(response.members);
            },
            error: function(xhr, status, error) {
                console.error("Dashboard stats fetch failed:", error);
                console.log("Response text:", xhr.responseText);
            }
        });
    }
}
if ($('.d-slider1').length > 0) {
    const options = {
        centeredSlides: false,
        loop: false,
        slidesPerView: 4,
        autoplay:false,
        spaceBetween: 32,
        breakpoints: {
            320: { slidesPerView: 1 },
            550: { slidesPerView: 2 },
            991: { slidesPerView: 3 },
            1400: { slidesPerView: 3 },
            1500: { slidesPerView: 4 },
            1920: { slidesPerView: 6 },
            2040: { slidesPerView: 7 },
            2440: { slidesPerView: 8 }
        },
        pagination: {
            el: '.swiper-pagination'
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev'
        },  

        // And if we need scrollbar
        scrollbar: {
            el: '.swiper-scrollbar'  
        }
    } 
    let swiper = new Swiper('.d-slider1',options);

    document.addEventListener('ChangeMode', (e) => {
      if (e.detail.rtl === 'rtl' || e.detail.rtl === 'ltr') {
        swiper.destroy(true, true)
        setTimeout(() => {
            swiper = new Swiper('.d-slider1',options);
        }, 500);
      }
    })
}

})(jQuery)

// Document ready to load charts only when functions exist (dashboard pages)
$(document).ready(function() {
    try {
        // Set the correct title immediately
        const ratioTitle = document.querySelector("#title-liquidity");
        if (ratioTitle) {
            ratioTitle.innerHTML = "STATEMENT OF FINANCIAL CONDITION";
        }
        
        if (typeof loadChartData === 'function') {
            console.log("=== PAGE LOADED - INITIALIZING CHARTS ===");
            const currentYear = $("#dashboard-year").val() || new Date().getFullYear();
            const compareYear = $("#dashboard-year-compare").val() || null;
            loadChartData(currentYear, compareYear);
            if (typeof loadIncomeBreakdownChart === 'function') loadIncomeBreakdownChart(currentYear, compareYear);
            if (typeof loadInventorySalesByDepartmentChart === 'function') loadInventorySalesByDepartmentChart(currentYear, compareYear);
            if (typeof loadTopSalesChart === 'function') loadTopSalesChart(currentYear);
            if (typeof loadPaidUnpaidChart === 'function') loadPaidUnpaidChart(currentYear, compareYear);
            if (typeof loadIncomeStatementChart === 'function') loadIncomeStatementChart(currentYear, compareYear);
            if (typeof loadClientTypeChart === 'function') loadClientTypeChart();
            if (typeof loadDashboardStats === 'function') loadDashboardStats();
        }
    } catch (e) {
        console.warn("Dashboard init skipped:", e);
    }
});

// Test: Force January text after page load
setTimeout(function() {
    console.log("=== FORCING JANUARY TEXT ===");
    $('#summary-year1-label').text('2021 January');
    $('#summary-year2-label').text('2020 January');
    console.log("January text forced - check if it appears");
}, 2000);
