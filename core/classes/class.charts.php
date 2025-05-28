<?php

class Charts
{


    public function chart1() {
        return <<<EOJS
        <script>
    /* -------------------------------------------------------------------------- */
    /*                      Echarts Total Sales E-commerce                        */
    /* -------------------------------------------------------------------------- */
    
    var totalSalesEcommerce = function totalSalesEcommerce() {
      var ECHART_LINE_TOTAL_SALES_ECOMM = '.echart-line-total-sales-ecommerce';
      var \$echartsLineTotalSalesEcomm = document.querySelector(ECHART_LINE_TOTAL_SALES_ECOMM);
      var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      function getFormatter(params) {
        return params.map(function (_ref19) {
          var value = _ref19.value,
            borderColor = _ref19.borderColor,
            seriesName = _ref19.seriesName;
          return "<span class= \\\"fas fa-circle\\\" style=\\\"color: ".concat(borderColor, "\\\"\\></span>
        <span class='text-600'>").concat(seriesName === 'lastMonth' ? 'Last Month' : 'Previous Year', ": ").concat(value, "</span>");
        }).join('<br/>');
      }
      if (\$echartsLineTotalSalesEcomm) {
        // Get options from data attribute
        var userOptions = utils.getData(\$echartsLineTotalSalesEcomm, 'options');
        var TOTAL_SALES_LAST_MONTH = "#".concat(userOptions.optionOne);
        var TOTAL_SALES_PREVIOUS_YEAR = "#".concat(userOptions.optionTwo);
        var totalSalesLastMonth = document.querySelector(TOTAL_SALES_LAST_MONTH);
        var totalSalesPreviousYear = document.querySelector(TOTAL_SALES_PREVIOUS_YEAR);
        var chart = window.echarts.init(\$echartsLineTotalSalesEcomm);
        var getDefaultOptions = function getDefaultOptions() {
          return {
            color: utils.getGrays()['100'],
            tooltip: {
              trigger: 'axis',
              padding: [7, 10],
              backgroundColor: utils.getGrays()['100'],
              borderColor: utils.getGrays()['300'],
              textStyle: {
                color: utils.getGrays()['1100']
              },
              borderWidth: 1,
              formatter: function formatter(params) {
                return getFormatter(params);
              },
              transitionDuration: 0,
              position: function position(pos, params, dom, rect, size) {
                return getPosition(pos, params, dom, rect, size);
              }
            },
            legend: {
              data: ['lastMonth', 'previousYear'],
              show: false
            },
            xAxis: {
              type: 'category',
              data: ['2019-01-05', '2019-01-06', '2019-01-07', '2019-01-08', '2019-01-09', '2019-01-10', '2019-01-11', '2019-01-12', '2019-01-13', '2019-01-14', '2019-01-15', '2019-01-16'],
              boundaryGap: false,
              axisPointer: {
                lineStyle: {
                  color: utils.getColor('gray-300'),
                  type: 'dashed'
                }
              },
              splitLine: {
                show: false
              },
              axisLine: {
                lineStyle: {
                  color: utils.rgbaColor('#000', 0.01),
                  type: 'dashed'
                }
              },
              axisTick: {
                show: false
              },
              axisLabel: {
                color: utils.getColor('gray-400'),
                formatter: function formatter(value) {
                  var date = new Date(value);
                  return "".concat(months[date.getMonth()], " ").concat(date.getDate());
                },
                margin: 15
              }
            },
            yAxis: {
              type: 'value',
              axisPointer: {
                show: false
              },
              splitLine: {
                lineStyle: {
                  color: utils.getColor('gray-300'),
                  type: 'dashed'
                }
              },
              boundaryGap: false,
              axisLabel: {
                show: true,
                color: utils.getColor('gray-400'),
                margin: 15
              },
              axisTick: {
                show: false
              },
              axisLine: {
                show: false
              }
            },
            series: [{
              name: 'lastMonth',
              type: 'line',
              data: [99, 99, 60, 80, 65, 90, 130, 90, 30, 40, 30, 70],
              lineStyle: {
                color: utils.getColor('primary')
              },
              itemStyle: {
                borderColor: utils.getColor('primary'),
                borderWidth: 2
              },
              symbol: 'circle',
              symbolSize: 10,
              hoverAnimation: true,
              areaStyle: {
                color: {
                  type: 'linear',
                  x: 0,
                  y: 0,
                  x2: 0,
                  y2: 1,
                  colorStops: [{
                    offset: 0,
                    color: utils.rgbaColor(utils.getColor('primary'), 0.2)
                  }, {
                    offset: 1,
                    color: utils.rgbaColor(utils.getColor('primary'), 0)
                  }]
                }
              }
            }, {
              name: 'previousYear',
              type: 'line',
              data: [110, 30, 40, 50, 80, 70, 50, 40, 110, 90, 60, 60],
              lineStyle: {
                color: utils.rgbaColor(utils.getColor('warning'), 0.3)
              },
              itemStyle: {
                borderColor: utils.rgbaColor(utils.getColor('warning'), 0.6),
                borderWidth: 2
              },
              symbol: 'circle',
              symbolSize: 10,
              hoverAnimation: true
            }],
            grid: {
              right: '18px',
              left: '40px',
              bottom: '15%',
              top: '5%'
            }
          };
        };
        echartSetOption(chart, userOptions, getDefaultOptions);
        totalSalesLastMonth.addEventListener('click', function () {
          chart.dispatchAction({
            type: 'legendToggleSelect',
            name: 'lastMonth'
          });
        });
        totalSalesPreviousYear.addEventListener('click', function () {
          chart.dispatchAction({
            type: 'legendToggleSelect',
            name: 'previousYear'
          });
        });
      }
    };
    </script>
    EOJS;
    }
    



# ##--------------------------------------------------------------------------------------------------------------------------------------------------
public function generateEChart($chartId, $dates, $dataLastMonth, $dataPreviousYear) {
    echo '<div id="' . $chartId . '" style="height: 400px;"></div>
    <script>
/* -------------------------------------------------------------------------- */
/*                      Echarts '.$chartId. '  */
/* -------------------------------------------------------------------------- */
/*
    document.addEventListener(\'DOMContentLoaded\', function() {
        var chartElement = document.getElementById("' . $chartId . '");

    if (chartElement) {
        var chart = echarts.init(chartElement);
        var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        function getFormatter(params) {
            return params.map(function ({ value, borderColor, seriesName }) {
                return `<span class="fas fa-circle" style="color: ${borderColor};"></span>
                        <span class="text-600">${seriesName === "lastMonth" ? "Last Month" : "Previous Year"}: ${value}</span>`;
            }).join("<br/>");
        }
        var options = {
            color: ["#5470C6", "#91CC75"],
            tooltip: {
                trigger: "axis",
                axisPointer: {
                    type: "cross",
                    label: {
                        backgroundColor: "#6a7985"
                    }
                },
                formatter: getFormatter
            },
            legend: {
                data: ["Last Month", "Previous Year"],
                show: false
            },
            grid: {
                right: "18px",
                left: "40px",
                bottom: "15%",
                top: "5%"
            },
            xAxis: {
                type: "category",
                data: ' . json_encode($dates) . ',
                boundaryGap: false,
                axisPointer: {
                    lineStyle: {
                        color: "gray",
                        type: "dashed"
                    }
                },
                splitLine: {
                    show: false
                },
                axisLine: {
                    lineStyle: {
                        color: "rgba(0, 0, 0, 0.01)",
                        type: "dashed"
                    }
                },
                axisTick: {
                    show: false
                },
                axisLabel: {
                    color: "gray",
                    formatter: function(value) {
                        var date = new Date(value);
                        return `${months[date.getMonth()]} ${date.getDate()}`;
                    },
                    margin: 15
                }
            },
            yAxis: {
                type: "value",
                axisPointer: {
                    show: false
                },
                splitLine: {
                    lineStyle: {
                        color: "gray",
                        type: "dashed"
                    }
                },
                boundaryGap: false,
                axisLabel: {
                    show: true,
                    color: "gray",
                    margin: 15
                },
                axisTick: {
                    show: false
                },
                axisLine: {
                    show: false
                }
            },
            series: [{
                name: "Last Month",
                type: "line",
                data: ' . json_encode($dataLastMonth) . ',
                lineStyle: {
                    color: "#5470C6"
                },
                itemStyle: {
                    borderColor: "#5470C6",
                    borderWidth: 2
                },
                symbol: "circle",
                symbolSize: 10,
                hoverAnimation: true,
                areaStyle: {
                    color: {
                        type: "linear",
                        x: 0,
                        y: 0,
                        x2: 0,
                        y2: 1,
                        colorStops: [{
                            offset: 0,
                            color: "rgba(84, 112, 198, 0.2)"
                        }, {
                            offset: 1,
                            color: "rgba(84, 112, 198, 0)"
                        }]
                    }
                }
            }, {
                name: "Previous Year",
                type: "line",
                data: ' . json_encode($dataPreviousYear) . ',
                lineStyle: {
                    color: "rgba(145, 204, 117, 0.3)"
                },
                itemStyle: {
                    borderColor: "rgba(145, 204, 117, 0.6)",
                    borderWidth: 2
                },
                symbol: "circle",
                symbolSize: 10,
                hoverAnimation: true
            }]
        };
        chart.setOption(options);
    }
});
    </script>';
}
}
