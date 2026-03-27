@props(['id'])

@once
    @push('styles')
        <style>
            .critical-alerts-stack {
                position: fixed;
                left: 12px;
                top: 50vh;
                bottom: 12px;
                z-index: 5000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                width: 320px;
                overflow-y: auto;
                overflow-x: hidden;
                padding-right: 4px;
            }
            .critical-alerts-stack.hidden {
                display: none;
            }
            .critical-alert-card {
                border: 1px solid rgba(248, 113, 113, 0.28);
                border-left: 3px solid rgba(248, 113, 113, 0.75);
                background: rgba(15, 23, 42, 0.96);
                box-shadow: 0 10px 24px rgb(2 6 23 / 0.45), 0 0 0 1px rgba(239, 68, 68, 0.08) inset;
                border-radius: 12px;
                padding: 10px 12px;
            }
            .critical-alert-icon {
                display: inline-flex;
                width: 20px;
                height: 20px;
                align-items: center;
                justify-content: center;
                color: #da1e28;
                flex: 0 0 auto;
            }
            .critical-alert-action {
                border: 1px solid #475569;
                background: rgba(15, 23, 42, .78);
                color: #fff;
                padding: 7px 12px;
                font-size: 13px;
            }
            .critical-alert-state-icon {
                display: block;
                overflow: visible;
            }
            .critical-alert-state-icon .state-hex-fill {
                fill: currentColor;
                stroke: currentColor;
                stroke-width: 2;
                stroke-linejoin: round;
            }
            .critical-alert-state-icon .state-mark-solid {
                fill: currentColor;
                stroke: none;
            }
            .critical-alert-state-icon .state-mark-contrast {
                fill: none;
                stroke: #f8fafc;
                stroke-width: 4.4;
                stroke-linecap: round;
                stroke-linejoin: round;
            }
        </style>
    @endpush
@endonce

<div id="{{ $id }}" class="critical-alerts-stack hidden"></div>
