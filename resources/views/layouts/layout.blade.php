<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'UKOM SPKKLP')</title>
    <link rel="stylesheet" href="{{ asset('vendors/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/ti-icons/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/datatables.net-bs4/dataTables.bootstrap4.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('js/select.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/vertical-layout-light/style.css') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="manifest" href="/site.webmanifest">
    @vite('resources/css/app.css')
    @stack('styles')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>

<body>
    <div class="container-scroller">
        @include('partials._navbar')
        <div class="container-fluid page-body-wrapper">
            @include('partials._sidebar')
            <div class="main-panel">
                <div class="content-wrapper">
                    @yield('content')
                </div>
                @include('partials._footer')
            </div>
        </div>
    </div>

    <form method="POST" action="/logout" id="logoutForm">
        @csrf
    </form>

    @include('components.delete-confirmation-modal')
    <script src="{{ asset('vendors/js/vendor.bundle.base.js') }}"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        window.rupiahInput = (() => {
            const formatter = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const parse = (value) => {
                if (value === null || value === undefined) {
                    return null;
                }

                if (typeof value === 'number') {
                    return Number.isFinite(value) ? value : null;
                }

                const text = String(value).trim();

                if (text === '') {
                    return null;
                }

                if (/^-?\d+(\.\d{1,2})?$/.test(text)) {
                    const parsedPlain = Number.parseFloat(text);
                    return Number.isFinite(parsedPlain) ? parsedPlain : null;
                }

                const sanitized = text.replace(/[^\d,-]/g, '').replace(/-/g, '');
                const segments = sanitized.split(',');
                const integerPart = (segments.shift() || '').replace(/\D/g, '');
                const fractionPart = segments.join('').replace(/\D/g, '').slice(0, 2);

                if (integerPart === '' && fractionPart === '') {
                    return null;
                }

                const normalized = `${integerPart || '0'}${fractionPart !== '' ? `.${fractionPart}` : ''}`;
                const parsed = Number.parseFloat(normalized);

                return Number.isFinite(parsed) ? parsed : null;
            };

            const format = (value) => {
                const parsed = parse(value);
                return parsed === null ? '' : formatter.format(parsed);
            };

            const formatTyping = (value) => {
                const raw = String(value ?? '').replace(/[^\d,]/g, '');

                if (raw === '') {
                    return '';
                }

                const hasComma = raw.includes(',');
                const segments = raw.split(',');
                const integerDigits = (segments.shift() || '').replace(/\D/g, '');
                const fractionDigits = segments.join('').replace(/\D/g, '').slice(0, 2);
                const integerFormatted = integerDigits === '' ?
                    '0' :
                    Number.parseInt(integerDigits, 10).toLocaleString('id-ID');

                return hasComma ? `${integerFormatted},${fractionDigits}` : integerFormatted;
            };

            const normalizeForm = (form) => {
                form.querySelectorAll('[data-rupiah-input]').forEach((input) => {
                    const parsed = parse(input.value);
                    input.value = parsed === null ? '' : parsed.toFixed(2);
                });
            };

            const bind = (input) => {
                if (input.dataset.rupiahBound === '1') {
                    return;
                }

                input.dataset.rupiahBound = '1';
                input.setAttribute('inputmode', 'decimal');

                input.addEventListener('input', () => {
                    input.value = formatTyping(input.value);
                });

                input.addEventListener('blur', () => {
                    input.value = format(input.value);
                });

                if (input.value.trim() !== '') {
                    input.value = format(input.value);
                }

                if (input.form && input.form.dataset.rupiahSubmitBound !== '1') {
                    input.form.dataset.rupiahSubmitBound = '1';
                    input.form.addEventListener('submit', () => normalizeForm(input.form));
                }
            };

            const init = (root = document) => {
                root.querySelectorAll('[data-rupiah-input]').forEach(bind);
            };

            return {
                parse,
                format,
                init,
                normalizeForm,
            };
        })();

        document.addEventListener('DOMContentLoaded', function() {
            window.rupiahInput.init();
        });

        document.getElementById('logoutButton').addEventListener('click', function(e) {
            document.getElementById('logoutForm').submit();
        });
    </script>
    <script src="{{ asset('vendors/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-bs4/dataTables.bootstrap4.js') }}"></script>
    <script src="{{ asset('js/dataTables.select.min.js') }}"></script>
    <script src="{{ asset('js/off-canvas.js') }}"></script>
    <script src="{{ asset('js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('js/template.js') }}"></script>
    <script src="{{ asset('js/settings.js') }}"></script>
    <script src="{{ asset('js/todolist.js') }}"></script>
    <script src="{{ asset('js/Chart.roundedBarCharts.js') }}"></script>
    <script>
        document.getElementById("year").textContent = new Date().getFullYear();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite('resources/js/datatable-general-config.js')
    @vite('resources/js/delete-confirmation-modal.js')
    @stack('scripts')
</body>

</html>
