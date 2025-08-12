import "../css/app.css";
import "./bootstrap";
import "./echo";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { VueQueryPlugin } from "@tanstack/vue-query";
import { queryClient } from "./Services/queryClient.js";

const appName = import.meta.env.VITE_APP_NAME || "Service Vault";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        // Initialize CSRF cookie for API requests
        if (window.initializeCSRF) {
            window.initializeCSRF();
        }

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueQueryPlugin, {
                queryClient,
                enableDevtoolsV6Plugin: true
            })
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
