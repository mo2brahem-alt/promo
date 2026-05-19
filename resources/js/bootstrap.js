import axios from 'axios';
window.axios = axios;

window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

window.refreshCsrfToken = async () => {
    const response = await window.axios.get('/sanctum/csrf-cookie');

    if (response.data?.csrf_token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.csrf_token;
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', response.data.csrf_token);
    }

    return response;
};
