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

window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;
        const isCsrfError = error.response?.status === 419;
        const isCsrfRefreshRequest = originalRequest?.url?.includes('/sanctum/csrf-cookie')
            || originalRequest?.url?.includes('/csrf-cookie');

        if (isCsrfError && originalRequest && !originalRequest.__csrfRetry && !isCsrfRefreshRequest) {
            originalRequest.__csrfRetry = true;
            await window.refreshCsrfToken();
            const refreshedToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (refreshedToken) {
                originalRequest.headers = originalRequest.headers || {};
                originalRequest.headers['X-CSRF-TOKEN'] = refreshedToken;
            }

            return window.axios(originalRequest);
        }

        return Promise.reject(error);
    },
);
