import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Login from './pages/Login.vue';
import Applications from './pages/Applications.vue';
import NewApplication from './pages/NewApplication.vue';
import ApplicationDetail from './pages/ApplicationDetail.vue';
import Reports from './pages/Reports.vue';
import PrintDocument from './pages/PrintDocument.vue';
import { auth, loadSession } from './auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/applications' },
        { path: '/login', component: Login, meta: { guest: true } },
        { path: '/applications', component: Applications },
        { path: '/applications/create', component: NewApplication, meta: { role: 'marketing' } },
        { path: '/applications/:id', component: ApplicationDetail },
        { path: '/applications/:id/print/:type', component: PrintDocument },
        { path: '/reports', component: Reports, meta: { roles: ['approver', 'backoffice'] } },
        { path: '/:pathMatch(.*)*', redirect: '/applications' },
    ],
});

router.beforeEach(async (to) => {
    await loadSession();
    if (!auth.user && !to.meta.guest) return '/login';
    if (auth.user && to.meta.guest) return '/applications';
    if (to.meta.role && auth.user?.group?.code !== to.meta.role) return '/applications';
    if (to.meta.roles && !to.meta.roles.includes(auth.user?.group?.code)) return '/applications';
});

createApp(App).use(router).mount('#app');
