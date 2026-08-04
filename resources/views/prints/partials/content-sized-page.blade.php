@php
    $contentSizedPrintTarget = $target ?? '#print-content';
    $contentSizedPrintWidth = (float) ($widthMm ?? 210);
    $contentSizedPrintMargin = (float) ($marginMm ?? 4);
    $contentSizedPrintMinHeight = (float) ($minHeightMm ?? 80);
    $contentSizedPrintMaxHeight = (float) ($maxHeightMm ?? 1200);
    $contentSizedPrintConfig = [
        'target' => $contentSizedPrintTarget,
        'widthMm' => $contentSizedPrintWidth,
        'marginMm' => $contentSizedPrintMargin,
        'minHeightMm' => $contentSizedPrintMinHeight,
        'maxHeightMm' => $contentSizedPrintMaxHeight,
    ];
@endphp
<script>
(function () {
    'use strict';

    const config = {{ \Illuminate\Support\Js::from($contentSizedPrintConfig) }};
    const PX_TO_MM = 25.4 / 96;
    const STYLE_ID = 'tb39-content-sized-page';

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function prepareContentSizedPrint() {
        const target = document.querySelector(config.target);
        if (!target) {
            return false;
        }

        const printableWidthMm = Math.max(1, config.widthMm - (config.marginMm * 2));
        const measurement = target.cloneNode(true);
        measurement.removeAttribute('id');
        measurement.querySelectorAll('.no-print, script').forEach(function (element) {
            element.remove();
        });
        Object.assign(measurement.style, {
            position: 'absolute',
            visibility: 'hidden',
            pointerEvents: 'none',
            left: '-100000px',
            top: '0',
            width: printableWidthMm + 'mm',
            maxWidth: 'none',
            minHeight: '0',
            height: 'auto',
            overflow: 'visible',
        });
        document.body.appendChild(measurement);
        const contentHeightPx = Math.max(
            measurement.scrollHeight || 0,
            measurement.offsetHeight || 0,
            measurement.getBoundingClientRect().height || 0
        );
        measurement.remove();

        const pageHeightMm = Math.ceil(clamp(
            (contentHeightPx * PX_TO_MM) + (config.marginMm * 2) + 3,
            config.minHeightMm,
            config.maxHeightMm
        ));

        let style = document.getElementById(STYLE_ID);
        if (!style) {
            style = document.createElement('style');
            style.id = STYLE_ID;
            document.head.appendChild(style);
        }

        style.textContent = `
            @page {
                size: ${config.widthMm}mm ${pageHeightMm}mm;
                margin: ${config.marginMm}mm;
            }
            @media print {
                html, body {
                    width: ${printableWidthMm}mm !important;
                    min-height: 0 !important;
                    height: auto !important;
                    overflow: visible !important;
                }
                ${config.target} {
                    min-height: 0 !important;
                    height: auto !important;
                    overflow: visible !important;
                }
            }
        `;

        return true;
    }

    window.prepareContentSizedPrint = prepareContentSizedPrint;
    window.printContentSized = function () {
        prepareContentSizedPrint();
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                window.focus();
                window.print();
            });
        });
    };

    document.addEventListener('DOMContentLoaded', prepareContentSizedPrint);
    window.addEventListener('load', prepareContentSizedPrint);
    window.addEventListener('beforeprint', prepareContentSizedPrint);
})();
</script>
