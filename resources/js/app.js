import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

/*
 * Stores et composants Alpine (Livewire fournit Alpine : on s'enregistre sur `alpine:init`).
 */
const registerSidebarStore = () => {
    const Alpine = window.Alpine;

    if (!Alpine || Alpine.store('sidebar')) {
        return;
    }

    Alpine.store('sidebar', {
        isExpanded: true,
        isMobileOpen: false,
        isHovered: false,

        init() {
            const saved = localStorage.getItem('sidebarExpanded');
            this.isExpanded = window.innerWidth >= 1280 ? (saved === null ? true : saved === 'true') : false;

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1280) {
                    this.isMobileOpen = false;
                    const state = localStorage.getItem('sidebarExpanded');
                    this.isExpanded = state === null ? true : state === 'true';
                } else {
                    this.isMobileOpen = false;
                }
            });

            // Referme le menu mobile après chaque navigation Livewire.
            document.addEventListener('livewire:navigated', () => {
                this.isMobileOpen = false;
            });
        },

        get showFull() {
            return this.isExpanded || this.isHovered || this.isMobileOpen;
        },

        toggleExpanded() {
            this.isExpanded = !this.isExpanded;
            this.isMobileOpen = false;

            if (window.innerWidth >= 1280) {
                localStorage.setItem('sidebarExpanded', this.isExpanded);
            }
        },

        toggleMobileOpen() {
            this.isMobileOpen = !this.isMobileOpen;
        },

        setMobileOpen(value) {
            this.isMobileOpen = value;
        },

        setHovered(value) {
            if (window.innerWidth >= 1280 && !this.isExpanded) {
                this.isHovered = value;
            }
        },
    });
};

const chartOptions = (config, dark) => {
    const text = dark ? '#98a2b3' : '#667085';
    const grid = dark ? '#1d2939' : '#e4e7ec';
    const common = {
        colors: config.colors ?? ['#465fff'],
        chart: {
            fontFamily: 'Outfit, sans-serif',
            height: config.height ?? 280,
            foreColor: text,
            toolbar: { show: false },
            background: 'transparent',
        },
        dataLabels: { enabled: false },
        tooltip: { theme: dark ? 'dark' : 'light' },
        noData: { text: 'Aucune donnée' },
    };

    if (config.type === 'donut') {
        return {
            ...common,
            chart: { ...common.chart, type: 'donut' },
            series: config.series,
            labels: config.labels,
            legend: { position: 'bottom', fontFamily: 'Outfit, sans-serif', markers: { size: 6 } },
            stroke: { width: 0 },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: config.totalLabel ?? 'Total',
                                color: text,
                                formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('fr-FR'),
                            },
                            value: { color: dark ? '#f2f4f7' : '#1d2939', fontSize: '28px', fontWeight: 600 },
                        },
                    },
                },
            },
        };
    }

    return {
        ...common,
        chart: { ...common.chart, type: config.type ?? 'bar' },
        series: config.series,
        plotOptions: { bar: { columnWidth: '42%', borderRadius: 6, borderRadiusApplication: 'end' } },
        stroke: { show: true, width: 4, colors: ['transparent'] },
        xaxis: {
            categories: config.categories ?? [],
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: { labels: { formatter: (v) => Math.round(v) }, forceNiceScale: true },
        grid: { borderColor: grid, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } } },
        legend: { show: false },
        fill: { opacity: 1 },
    };
};

const registerChartComponent = () => {
    window.Alpine.data('apexChart', (config) => ({
        chart: null,
        observer: null,

        init() {
            const isDark = () => document.documentElement.classList.contains('dark');

            this.$nextTick(() => {
                this.chart = new ApexCharts(this.$refs.el, chartOptions(config, isDark()));
                this.chart.render();

                this.observer = new MutationObserver(() => {
                    this.chart?.updateOptions(chartOptions(config, isDark()), false, false);
                });
                this.observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            });
        },

        destroy() {
            this.observer?.disconnect();
            this.chart?.destroy();
        },
    }));
};

document.addEventListener('alpine:init', () => {
    registerSidebarStore();
    registerChartComponent();
});

if (window.Alpine) {
    registerSidebarStore();
}
