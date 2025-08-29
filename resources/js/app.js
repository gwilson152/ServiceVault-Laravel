import "../css/app.css";
import "./bootstrap";
import "./echo";

import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { VueQueryPlugin } from "@tanstack/vue-query";
import { queryClient } from "./Services/queryClient.js";
import { createPinia } from "pinia";

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

        // Update CSRF token if provided via Inertia props
        if (props.initialPage?.props?.csrf_token && window.updateCSRFToken) {
            window.updateCSRFToken(props.initialPage.props.csrf_token);
        }

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
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

// Global Inertia page listener to update CSRF tokens
router.on('navigate', (event) => {
    // Update CSRF token on every page navigation if provided
    if (event.detail.page?.props?.csrf_token && window.updateCSRFToken) {
        window.updateCSRFToken(event.detail.page.props.csrf_token);
    }
});
