<svg {{ $attributes->class([]) }} viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <title>StuFis Logo</title>
    <defs>
        <!-- fill color hexagon -->
        <linearGradient id="{{ $idPrefix }}_lin1" x1="96.5" y1="71.08" x2="427.47" y2="456.86" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="{{ $col1 }}"/>
            <stop offset="1" stop-color="{{ $col2 }}"/>
        </linearGradient>
        <!-- inner color -->
        <linearGradient id="{{ $idPrefix }}_lin2" x1="187.53" y1="122.23" x2="396.49" y2="468.55" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#fff"/>
            <stop offset="1" stop-color="#e6e6e6"/>
        </linearGradient>
        <linearGradient id="{{ $idPrefix }}_lin22" href="#{{ $idPrefix }}_lin2" x1="146.43" y1="147.03" x2="355.39" y2="493.35" />
        <linearGradient id="{{ $idPrefix }}_lin23" href="#{{ $idPrefix }}_lin2" x1="122.52" y1="161.46" x2="331.48" y2="507.78" />
    </defs>
    <!-- hexagon -->
    <polygon fill="url(#{{ $idPrefix }}_lin1)" points="373.53 36.05 126.47 36.05 2.95 250 126.47 463.95 373.53 463.95 497.05 250 373.53 36.05"/>
    <!-- euro sign -->
    <g filter="drop-shadow(12 12 4 rgba(0 0 0 / 0.25))">
        <path fill="url(#{{ $idPrefix }}_lin2)" d="M272,391c-77.38,0-140.39-62.66-141-139.89-.58-74.76,58.55-137.87,133.2-141.88a140.58,140.58,0,0,1,82.13,20.94,25.21,25.21,0,0,1,4.52,39h0a112.51,112.51,0,0,0-86.07-31.87c-56.51,3.5-102.28,49.49-105.58,106A113,113,0,0,0,350.9,330.89h0a25.24,25.24,0,0,1-4.64,39A140.21,140.21,0,0,1,272,391Z"/>
        <path fill="url(#{{ $idPrefix }}_lin22)" d="M248.21,237H108.29a28,28,0,0,1,28-28H276.21A28,28,0,0,1,248.21,237Z"/>
        <path fill="url(#{{ $idPrefix }}_lin23)" d="M248.21,291H108.29a28,28,0,0,1,28-28H276.21A28,28,0,0,1,248.21,291Z"/>
    </g>
</svg>
