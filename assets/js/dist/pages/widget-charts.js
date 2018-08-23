/*
Template Name: Admin Template
Author: Wrappixel

File: js
*/
import Chartist from 'chartist';

$(function () {
    "use strict";
    // ============================================================== 
    // User analytics
    // ==============================================================
    // console.log(typeof axisX + typeof chartData + typeof maxValue);
    if (typeof axisX !== 'undefined' && typeof chartData !== 'undefined' && typeof maxValue !== 'undefined'){
        new Chartist.Line('.user-analytics', {
                labels: axisX,
                series: [chartData]
            },
            {
                width: '100%',
                high: maxValue,
                low: 0,
                showArea: true,
                lineSmooth: Chartist.Interpolation.simple({
                    divisor: 10
                }),
                fullWidth: true,
                chartPadding: {
                    top: 20,
                    right: 60,
                    bottom: 30,
                    left: 0
                },
                plugins: [
                    Chartist.plugins.tooltip()
                ], // As this is axis specific we need to tell Chartist to use whole numbers only on the concerned axis
                axisY: {
                    onlyInteger: true,
                    offset: 50,
                    labelInterpolationFnc: function (value) {
                        return (value / 1) + '%';
                    }
                }
            });
    }

});
