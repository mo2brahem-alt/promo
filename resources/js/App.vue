<template>
    <section v-if="loading" class="login-page">
        <div class="login-box">جاري تحميل النظام...</div>
    </section>

    <section v-else-if="!user" class="login-page">
        <form class="login-box grid" @submit.prevent="login">
            <div>
                <div class="brand" style="color: var(--text)">
                    <div class="brand-mark">P</div>
                    <div>
                        <h1 style="margin:0">Promo</h1>
                        <p class="label" style="margin:4px 0 0">منصة إدارة Promo Codes</p>
                    </div>
                </div>
            </div>
            <div v-if="message" class="notice error">{{ message }}</div>
            <label class="field">
                <span>البريد الإلكتروني</span>
                <input v-model="loginForm.email" class="input" type="email" required>
            </label>
            <label class="field">
                <span>كلمة المرور</span>
                <input v-model="loginForm.password" class="input" type="password" required>
            </label>
            <button class="btn primary" :disabled="busy">دخول</button>
        </form>
    </section>

    <section v-else class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">P</div>
                <div>
                    <strong>Promo</strong>
                    <div class="label" style="color:#bdc7d8">{{ roleLabel(user.role) }}</div>
                </div>
            </div>
            <nav class="nav">
                <button v-for="item in navItems" :key="item.view" :class="{ active: view === item.view }" @click="navigate(item.view)">
                    {{ item.label }}
                </button>
            </nav>
        </aside>

        <main class="main">
            <header class="topbar">
                <div>
                    <h2 style="margin:0">{{ currentTitle }}</h2>
                    <div class="label">{{ user.name }} - {{ roleLabel(user.role) }}</div>
                </div>
                <button class="btn" @click="logout">تسجيل الخروج</button>
            </header>

            <div class="content">
                <div v-if="message" :class="['notice', messageType === 'error' ? 'error' : '']">{{ message }}</div>
                <DashboardView v-if="view === 'dashboard'" :dashboard="dashboard" :can-reports="canReports" @go="setView" />
                <SellerView v-if="view === 'seller'" :validation="seller.validation" :last="seller.last" :busy="busy" @validate="validatePromo" @redeem="redeemPromo" />
                <CustomersView v-if="view === 'customers'" :customers="customers" :busy="busy" @search="loadCustomers" @save="saveCustomer" @edit="editCustomer" @remove="deleteCustomer" @toggle="toggleCustomer" @import="importCustomers" @template="downloadTemplate" />
                <BranchesView v-if="view === 'branches'" :branches="branches" :users="users" :busy="busy" @search="loadBranches" @save="saveBranch" @edit="editBranch" @remove="deleteBranch" @toggle="toggleBranch" />
                <PromoCodesView v-if="view === 'promoCodes'" :promo-codes="promoCodes" :customers="customersList" :branches="branchesList" :busy="busy" @search="loadPromoCodes" @save="savePromoCode" @edit="editPromoCode" @remove="deletePromoCode" @toggle="togglePromoCode" @generate="generatePromoCode" />
                <ReportsView v-if="view === 'reports'" :reports="reports" :customers="customersList" :branches="branchesList" :users="users" :promo-codes="promoCodesList" @filter="loadReports" @export="exportReports" />
                <UsersView v-if="view === 'users'" :users="users" :branches="branchesList" :busy="busy" @save="saveUser" @toggle="toggleUser" @password="changeUserPassword" @reload="loadUsers" />
            </div>
        </main>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const user = ref(null);
const loading = ref(true);
const busy = ref(false);
const view = ref(viewFromPath(window.location.pathname));
const message = ref('');
const messageType = ref('info');
const loginForm = reactive({ email: 'superadmin@example.com', password: 'password' });

const dashboard = ref({});
const customers = ref({ data: [] });
const branches = ref({ data: [] });
const promoCodes = ref({ data: [] });
const reports = ref({ data: { data: [] }, summary: {} });
const users = ref([]);
const seller = reactive({ validation: null, last: [] });
const draftCustomer = ref(null);
const draftBranch = ref(null);
const draftPromo = ref(null);

const canManage = computed(() => ['super_admin', 'admin', 'promo_manager'].includes(user.value?.role));
const canReports = computed(() => ['super_admin', 'admin', 'promo_manager', 'reports_manager'].includes(user.value?.role));
const navItems = computed(() => {
    if (user.value?.role === 'seller') {
        return [{ view: 'seller', label: 'تفعيل البرومو' }];
    }

    const items = [{ view: 'dashboard', label: 'لوحة التحكم' }];
    if (canManage.value) {
        items.push({ view: 'customers', label: 'العملاء' });
        items.push({ view: 'branches', label: 'الفروع' });
        items.push({ view: 'promoCodes', label: 'الأكواد' });
    }
    if (canReports.value) items.push({ view: 'reports', label: 'التقارير' });
    if (user.value?.role === 'super_admin') items.push({ view: 'users', label: 'المستخدمون' });
    return items;
});
const currentTitle = computed(() => navItems.value.find((item) => item.view === view.value)?.label || 'Promo');
const customersList = computed(() => customers.value.data || []);
const branchesList = computed(() => branches.value.data || []);
const promoCodesList = computed(() => promoCodes.value.data || []);

const viewPaths = {
    dashboard: '/dashboard',
    seller: '/seller/promo-validation',
    customers: '/customers',
    branches: '/branches',
    promoCodes: '/promo-codes',
    reports: '/reports',
    users: '/users',
};

function viewFromPath(path) {
    const normalized = path.replace(/\/+$/, '') || '/';

    return {
        '/': 'dashboard',
        '/dashboard': 'dashboard',
        '/seller/promo-validation': 'seller',
        '/customers': 'customers',
        '/branches': 'branches',
        '/promo-codes': 'promoCodes',
        '/reports': 'reports',
        '/users': 'users',
    }[normalized] || 'dashboard';
}

function roleLabel(role) {
    return {
        super_admin: 'Super Admin',
        admin: 'مدير النظام',
        promo_manager: 'مدير البرومو',
        seller: 'بائع',
        reports_manager: 'مدير التقارير',
    }[role] || role;
}

function flash(text, type = 'info') {
    message.value = text;
    messageType.value = type;
    window.setTimeout(() => {
        if (message.value === text) message.value = '';
    }, 5000);
}

function errorText(error) {
    if (error.response?.status === 419) {
        return 'انتهت الجلسة، برجاء تحديث الصفحة والمحاولة مرة أخرى.';
    }

    const data = error.response?.data;
    if (data?.errors) return Object.values(data.errors).flat().join(' ');
    return data?.message || 'حدث خطأ غير متوقع.';
}

async function api(call, success) {
    busy.value = true;
    try {
        const response = await call();
        if (success) flash(success);
        return response.data;
    } catch (error) {
        flash(errorText(error), 'error');
        throw error;
    } finally {
        busy.value = false;
    }
}

async function loadMe() {
    try {
        const { data } = await axios.get('/api/me');
        user.value = data.user;
    } catch {
        user.value = null;
    } finally {
        loading.value = false;
    }
}

async function login() {
    await window.refreshCsrfToken();
    const data = await api(() => axios.post('/login', loginForm), 'تم تسجيل الدخول.');
    user.value = data.user;
    if (user.value?.role === 'seller') view.value = 'seller';
    await bootstrapView();
}

async function logout() {
    await axios.post('/logout');
    user.value = null;
    view.value = 'dashboard';
    window.history.pushState({}, '', '/');
}

async function setView(next) {
    view.value = next;
    try {
        await bootstrapView();
    } catch (error) {
        flash(errorText(error), 'error');
    }
}

async function navigate(next) {
    window.history.pushState({}, '', viewPaths[next] || '/dashboard');
    await setView(next);
}

async function bootstrapView() {
    const tasks = [loadDashboard()];

    if (canManage.value || canReports.value) {
        tasks.push(loadCustomers('', 1, true), loadBranches('', 1, true), loadPromoCodes('', 1, true));
    }

    if (user.value?.role === 'super_admin' || canManage.value || canReports.value) tasks.push(loadUsers());
    if (view.value === 'seller' || user.value?.role === 'seller') tasks.push(loadSellerLast());
    if (view.value === 'reports') tasks.push(loadReports({}));

    const results = await Promise.allSettled(tasks);
    const failed = results.find((result) => result.status === 'rejected');

    if (failed) {
        flash(errorText(failed.reason), 'error');
    }
}

async function loadDashboard() {
    dashboard.value = await api(() => axios.get('/api/dashboard'));
}

async function loadUsers() {
    const data = await api(() => axios.get('/api/users', { params: { per_page: 100 } }));
    users.value = data.data || data;
}

async function saveUser(payload) {
    const id = payload.id;
    await api(() => id ? axios.put(`/api/users/${id}`, payload) : axios.post('/api/users', payload), id ? 'تم تحديث المستخدم.' : 'تم إنشاء المستخدم.');
    await loadUsers();
}

async function toggleUser(userRow) {
    await api(() => axios.patch(`/api/users/${userRow.id}/toggle`, { is_active: !userRow.is_active }), 'تم تحديث حالة المستخدم.');
    await loadUsers();
}

async function changeUserPassword(payload) {
    await api(() => axios.patch(`/api/users/${payload.id}/password`, payload), 'تم تغيير كلمة المرور.');
    await loadUsers();
}

async function loadCustomers(search = '', page = 1, silent = false) {
    const data = await api(() => axios.get('/api/customers', { params: { search, page, per_page: 50 } }));
    customers.value = data;
    if (!silent && search) flash('تم تحديث نتائج العملاء.');
}

async function saveCustomer(payload) {
    const id = payload.id;
    await api(() => id ? axios.put(`/api/customers/${id}`, payload) : axios.post('/api/customers', payload), 'تم حفظ العميل.');
    draftCustomer.value = null;
    await loadCustomers();
}

function editCustomer(customer) {
    draftCustomer.value = { ...customer };
}

async function deleteCustomer(customer) {
    if (!confirm('هل تريد حذف هذا العميل؟')) return;
    await api(() => axios.delete(`/api/customers/${customer.id}`), 'تم حذف العميل.');
    await loadCustomers();
}

async function toggleCustomer(customer) {
    await api(() => axios.patch(`/api/customers/${customer.id}/toggle`, { is_active: !customer.is_active }), 'تم تحديث حالة العميل.');
    await loadCustomers();
}

async function importCustomers({ file, duplicate_strategy }) {
    const form = new FormData();
    form.append('file', file);
    form.append('duplicate_strategy', duplicate_strategy);
    const result = await api(() => axios.post('/api/customers/import', form), 'تم استيراد الملف.');
    await loadCustomers();
    flash(`الصفوف: ${result.total_rows} | مضاف: ${result.created} | محدث: ${result.updated} | متجاهل: ${result.skipped} | فاشل: ${result.failed}`);
}

function downloadTemplate() {
    window.location.href = '/api/customers-template';
}

async function loadBranches(search = '', page = 1, silent = false) {
    branches.value = await api(() => axios.get('/api/branches', { params: { search, page, per_page: 50 } }));
    if (!silent && search) flash('تم تحديث نتائج الفروع.');
}

async function saveBranch(payload) {
    const id = payload.id;
    await api(() => id ? axios.put(`/api/branches/${id}`, payload) : axios.post('/api/branches', payload), 'تم حفظ الفرع.');
    draftBranch.value = null;
    await loadBranches();
}

function editBranch(branch) {
    draftBranch.value = { ...branch, seller_ids: (branch.users || []).map((u) => u.id) };
}

async function deleteBranch(branch) {
    if (!confirm('هل تريد حذف هذا الفرع؟')) return;
    await api(() => axios.delete(`/api/branches/${branch.id}`), 'تم حذف الفرع.');
    await loadBranches();
}

async function toggleBranch(branch) {
    await api(() => axios.patch(`/api/branches/${branch.id}/toggle`, { is_active: !branch.is_active }), 'تم تحديث حالة الفرع.');
    await loadBranches();
}

async function loadPromoCodes(search = '', page = 1, silent = false) {
    promoCodes.value = await api(() => axios.get('/api/promo-codes', { params: { search, page, per_page: 50 } }));
    if (!silent && search) flash('تم تحديث نتائج الأكواد.');
}

async function generatePromoCode(setCode = null) {
    const data = await api(() => axios.get('/api/promo-codes-generate'));
    if (typeof setCode === 'function') setCode(data.code);
    return data.code;
}

async function savePromoCode(payload) {
    const id = payload.id;
    await api(() => id ? axios.put(`/api/promo-codes/${id}`, payload) : axios.post('/api/promo-codes', payload), 'تم حفظ البرومو كود.');
    draftPromo.value = null;
    await loadPromoCodes();
}

function editPromoCode(code) {
    draftPromo.value = {
        ...code,
        customers: (code.customers || []).map((c) => c.id),
        branches: (code.branches || []).map((b) => b.id),
    };
}

async function deletePromoCode(code) {
    if (!confirm('هل تريد حذف هذا الكود؟')) return;
    await api(() => axios.delete(`/api/promo-codes/${code.id}`), 'تم حذف الكود.');
    await loadPromoCodes();
}

async function togglePromoCode(code) {
    await api(() => axios.patch(`/api/promo-codes/${code.id}/toggle`, { is_active: !code.is_active }), 'تم تحديث حالة الكود.');
    await loadPromoCodes();
}

async function validatePromo(payload) {
    seller.validation = await api(() => axios.post('/api/seller/validate-promo', payload), 'الكود صالح للاستخدام.');
}

async function redeemPromo(payload) {
    const data = await api(() => axios.post('/api/seller/redeem-promo', payload), 'تم تفعيل البرومو بنجاح.');
    seller.validation = { ...seller.validation, redemption: data.redemption };
    await loadSellerLast();
}

async function loadSellerLast() {
    if (user.value?.role !== 'seller') return;
    seller.last = await api(() => axios.get('/api/seller/redemptions'));
}

async function loadReports(filters = {}) {
    reports.value = await api(() => axios.get('/api/reports/redemptions', { params: filters }));
}

function exportReports(filters = {}) {
    const params = new URLSearchParams(filters).toString();
    window.location.href = `/api/reports/redemptions/export?${params}`;
}

onMounted(async () => {
    window.addEventListener('popstate', () => {
        setView(viewFromPath(window.location.pathname));
    });

    await loadMe();
    if (user.value?.role === 'seller') {
        view.value = 'seller';
        if (window.location.pathname !== viewPaths.seller) {
            window.history.replaceState({}, '', viewPaths.seller);
        }
    }
    if (user.value) await bootstrapView();
});
</script>

<script>
const EmptyRow = {
    props: ['cols', 'text'],
    template: `<tr><td :colspan="cols" class="label">{{ text || 'لا توجد بيانات' }}</td></tr>`,
};

const RecentTable = {
    components: { EmptyRow },
    props: ['rows'],
    template: `
        <div class="table-wrap">
            <table>
                <thead><tr><th>الكود</th><th>العميل</th><th>الفرع</th><th>الفاتورة</th><th>الخصم</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id">
                        <td>{{ row.promo_code?.code }}</td>
                        <td>{{ row.customer?.name }}<div class="label">{{ row.customer?.phone }}</div></td>
                        <td>{{ row.branch?.name }}</td>
                        <td>{{ row.invoice_number }}<div class="label">{{ row.invoice_amount }}</div></td>
                        <td>{{ row.discount_amount }}</td>
                        <td>{{ row.redeemed_at }}</td>
                    </tr>
                    <EmptyRow v-if="!rows || !rows.length" :cols="6" />
                </tbody>
            </table>
        </div>
    `,
};

const DashboardView = {
    components: { RecentTable },
    props: ['dashboard', 'canReports'],
    emits: ['go'],
    template: `
        <section class="grid">
            <div v-if="dashboard.role === 'seller'" class="panel">
                <div class="panel-head">
                    <div><h3 style="margin:0">مساحة البائع</h3><div class="label">ابدأ من شاشة تفعيل البرومو</div></div>
                    <button class="btn primary" @click="$emit('go', 'seller')">فتح التفعيل</button>
                </div>
                <RecentTable :rows="dashboard.recent_redemptions || []" />
            </div>
            <template v-else>
                <div class="cards">
                    <div class="card"><div class="label">العملاء</div><div class="metric">{{ dashboard.customers_count || 0 }}</div></div>
                    <div class="card"><div class="label">الفروع</div><div class="metric">{{ dashboard.branches_count || 0 }}</div></div>
                    <div class="card"><div class="label">إجمالي الأكواد</div><div class="metric">{{ dashboard.promo_codes_count || 0 }}</div></div>
                    <div class="card"><div class="label">الأكواد النشطة</div><div class="metric">{{ dashboard.active_codes_count || 0 }}</div></div>
                    <div class="card"><div class="label">استخدامات اليوم</div><div class="metric">{{ dashboard.today_redemptions_count || 0 }}</div></div>
                    <div class="card"><div class="label">خصومات اليوم</div><div class="metric">{{ dashboard.today_discount_amount || 0 }}</div></div>
                </div>
                <div class="panel">
                    <div class="panel-head"><h3 style="margin:0">آخر 10 استخدامات</h3><button v-if="canReports" class="btn" @click="$emit('go', 'reports')">التقارير</button></div>
                    <RecentTable :rows="dashboard.recent_redemptions || []" />
                </div>
            </template>
        </section>
    `,
};

const SellerView = {
    props: ['validation', 'last', 'busy'],
    emits: ['validate', 'redeem'],
    data: () => ({ form: { code: 'WELCOME10', customer_phone: '01000000001' }, invoice: { invoice_number: '', invoice_amount: '' } }),
    methods: {
        redeem() {
            this.$emit('redeem', { ...this.form, ...this.invoice });
        },
    },
    components: { RecentTable },
    template: `
        <section class="grid">
            <div class="panel">
                <div class="panel-head"><h3 style="margin:0">Seller Promo Validation</h3></div>
                <form class="form" @submit.prevent="$emit('validate', form)">
                    <label class="field"><span>Promo Code</span><input class="input" v-model="form.code" required></label>
                    <label class="field"><span>رقم جوال العميل</span><input class="input" v-model="form.customer_phone" required></label>
                    <div class="field"><span>&nbsp;</span><button class="btn primary" :disabled="busy">تحقق</button></div>
                </form>
            </div>
            <div v-if="validation" class="panel">
                <div class="cards">
                    <div class="card"><div class="label">العميل</div><strong>{{ validation.customer.name }}</strong><div class="label">{{ validation.customer.phone }}</div></div>
                    <div class="card"><div class="label">الكود</div><strong>{{ validation.promo_code.code }}</strong><div class="label">{{ validation.promo_code.title }}</div></div>
                    <div class="card"><div class="label">نسبة الخصم</div><strong>{{ validation.promo_code.discount_value }}%</strong></div>
                    <div class="card"><div class="label">الفرع الحالي</div><strong>{{ validation.branch.name }}</strong></div>
                </div>
                <form class="form" style="margin-top:16px" @submit.prevent="redeem">
                    <label class="field"><span>رقم الفاتورة من POS</span><input class="input" v-model="invoice.invoice_number" required></label>
                    <label class="field"><span>قيمة الفاتورة</span><input class="input" type="number" step="0.01" min="0.01" v-model="invoice.invoice_amount" required></label>
                    <div class="field"><span>&nbsp;</span><button class="btn blue" :disabled="busy">تفعيل البرومو</button></div>
                </form>
                <div v-if="validation.redemption" class="notice" style="margin-top:16px">
                    تم التفعيل. الفاتورة: {{ validation.redemption.invoice_number }} | قيمة الخصم: {{ validation.redemption.discount_amount }} | الوقت: {{ validation.redemption.redeemed_at }}
                </div>
            </div>
            <div class="panel"><h3>آخر استخداماتي</h3><RecentTable :rows="last" /></div>
        </section>
    `,
};

const CustomersView = {
    props: ['customers', 'busy'],
    emits: ['search', 'save', 'edit', 'remove', 'toggle', 'import', 'template'],
    data: () => ({ search: '', form: { name: '', phone: '', email: '', city: '', notes: '', is_active: true }, importFile: null, duplicate_strategy: 'update' }),
    methods: {
        submit() { this.$emit('save', this.form); this.form = { name: '', phone: '', email: '', city: '', notes: '', is_active: true }; },
        pick(row) { this.form = { ...row }; },
        reset() { this.form = { name: '', phone: '', email: '', city: '', notes: '', is_active: true }; },
        doImport() { if (this.importFile) this.$emit('import', { file: this.importFile, duplicate_strategy: this.duplicate_strategy }); },
    },
    components: { EmptyRow },
    template: `
        <section class="grid">
            <div class="panel">
                <div class="panel-head"><h3 style="margin:0">العملاء</h3><div class="actions"><button class="btn primary" @click="reset">إضافة عميل</button><input class="input" style="width:260px" v-model="search" placeholder="بحث بالاسم أو الجوال"><button class="btn" @click="$emit('search', search)">بحث</button></div></div>
                <form class="form" @submit.prevent="submit">
                    <label class="field"><span>الاسم</span><input class="input" v-model="form.name" required></label>
                    <label class="field"><span>الجوال</span><input class="input" v-model="form.phone" required></label>
                    <label class="field"><span>البريد</span><input class="input" type="email" v-model="form.email"></label>
                    <label class="field"><span>المدينة</span><input class="input" v-model="form.city"></label>
                    <label class="field wide"><span>ملاحظات</span><textarea class="textarea" v-model="form.notes"></textarea></label>
                    <label class="check-row"><input type="checkbox" v-model="form.is_active"> نشط</label>
                    <div class="actions wide"><button class="btn primary" :disabled="busy">حفظ العميل</button><button type="button" class="btn" @click="form = { name: '', phone: '', email: '', city: '', notes: '', is_active: true }">جديد</button></div>
                </form>
            </div>
            <div class="panel">
                <div class="panel-head"><h3 style="margin:0">رفع Excel</h3><button class="btn" @click="$emit('template')">تنزيل نموذج</button></div>
                <div class="actions"><input class="input" type="file" @change="importFile = $event.target.files[0]"><select class="select" v-model="duplicate_strategy" style="width:180px"><option value="update">تحديث الموجود</option><option value="skip">تجاهل المكرر</option></select><button class="btn blue" @click="doImport">رفع الملف</button></div>
            </div>
            <div class="panel table-wrap">
                <table><thead><tr><th>الاسم</th><th>الجوال</th><th>المدينة</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody>
                    <tr v-for="row in customers.data" :key="row.id"><td>{{ row.name }}</td><td>{{ row.phone }}<div class="label">{{ row.email }}</div></td><td>{{ row.city }}</td><td><span :class="['badge', row.is_active ? 'on' : 'off']">{{ row.is_active ? 'نشط' : 'متوقف' }}</span></td><td class="actions"><button class="btn" @click="pick(row)">تعديل</button><button class="btn" @click="$emit('toggle', row)">{{ row.is_active ? 'تعطيل' : 'تفعيل' }}</button></td></tr>
                    <EmptyRow v-if="!customers.data?.length" :cols="5" />
                </tbody></table>
            </div>
        </section>
    `,
};

const BranchesView = {
    props: ['branches', 'users', 'busy'],
    emits: ['search', 'save', 'remove', 'toggle'],
    data: () => ({ search: '', form: { name: '', code: '', city: '', address: '', is_active: true, seller_ids: [] } }),
    computed: { sellers() { return this.users.filter((u) => u.role === 'seller'); } },
    methods: {
        pick(row) { this.form = { ...row, seller_ids: (row.users || []).map((u) => u.id) }; },
        submit() { this.$emit('save', this.form); this.form = { name: '', code: '', city: '', address: '', is_active: true, seller_ids: [] }; },
        reset() { this.form = { name: '', code: '', city: '', address: '', is_active: true, seller_ids: [] }; },
    },
    components: { EmptyRow },
    template: `
        <section class="grid">
            <div class="panel">
                <div class="panel-head"><h3 style="margin:0">الفروع</h3><div class="actions"><button class="btn primary" @click="reset">إضافة فرع</button><input class="input" style="width:260px" v-model="search" placeholder="بحث"><button class="btn" @click="$emit('search', search)">بحث</button></div></div>
                <form class="form" @submit.prevent="submit">
                    <label class="field"><span>اسم الفرع</span><input class="input" v-model="form.name" required></label>
                    <label class="field"><span>كود الفرع</span><input class="input" v-model="form.code" required></label>
                    <label class="field"><span>المدينة</span><input class="input" v-model="form.city"></label>
                    <label class="field wide"><span>العنوان</span><input class="input" v-model="form.address"></label>
                    <div class="field wide"><span>البائعون المرتبطون</span><div class="multi-list"><label v-for="seller in sellers" :key="seller.id" class="check-row"><input type="checkbox" :value="seller.id" v-model="form.seller_ids"> {{ seller.name }}</label></div></div>
                    <label class="check-row"><input type="checkbox" v-model="form.is_active"> نشط</label>
                    <div class="actions wide"><button class="btn primary">حفظ الفرع</button><button type="button" class="btn" @click="form = { name: '', code: '', city: '', address: '', is_active: true, seller_ids: [] }">جديد</button></div>
                </form>
            </div>
            <div class="panel table-wrap">
                <table><thead><tr><th>الفرع</th><th>الكود</th><th>المدينة</th><th>البائعون</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody>
                    <tr v-for="row in branches.data" :key="row.id"><td>{{ row.name }}</td><td>{{ row.code }}</td><td>{{ row.city }}</td><td>{{ row.sellers_count ?? (row.users || []).length }}</td><td><span :class="['badge', row.is_active ? 'on' : 'off']">{{ row.is_active ? 'نشط' : 'متوقف' }}</span></td><td class="actions"><button class="btn" @click="pick(row)">تعديل</button><button class="btn" @click="$emit('toggle', row)">{{ row.is_active ? 'تعطيل' : 'تفعيل' }}</button></td></tr>
                    <EmptyRow v-if="!branches.data?.length" :cols="6" />
                </tbody></table>
            </div>
        </section>
    `,
};

const PromoCodesView = {
    props: ['promoCodes', 'customers', 'branches', 'busy'],
    emits: ['search', 'save', 'remove', 'toggle', 'generate'],
    data: () => ({ search: '', form: { code: '', title: '', description: '', discount_type: 'percentage', discount_value: 10, max_invoice_amount: '', max_discount_amount: '', starts_at: '', expires_at: '', max_total_uses: '', max_uses_per_customer: 1, is_active: true, customers: [], branches: [] } }),
    methods: {
        pick(row) { this.form = { ...row, customers: (row.customers || []).map(c => c.id), branches: (row.branches || []).map(b => b.id), starts_at: row.starts_at?.slice(0,16), expires_at: row.expires_at?.slice(0,16) }; },
        submit() { this.$emit('save', this.form); },
        async gen() { this.form.code = await this.$emit('generate'); },
        clear() { this.form = { code: '', title: '', description: '', discount_type: 'percentage', discount_value: 10, max_invoice_amount: '', max_discount_amount: '', starts_at: '', expires_at: '', max_total_uses: '', max_uses_per_customer: 1, is_active: true, customers: [], branches: [] }; },
    },
    components: { EmptyRow },
    template: `
        <section class="grid">
            <div class="panel">
                <div class="panel-head"><h3 style="margin:0">الأكواد</h3><div class="actions"><button class="btn primary" @click="clear">إنشاء كود</button><input class="input" style="width:260px" v-model="search" placeholder="بحث بالكود أو العنوان"><button class="btn" @click="$emit('search', search)">بحث</button></div></div>
                <form class="form" @submit.prevent="submit">
                    <label class="field"><span>الكود</span><div class="actions"><input class="input" v-model="form.code" required><button type="button" class="btn" @click="$emit('generate', code => form.code = code)">توليد</button></div></label>
                    <label class="field"><span>العنوان</span><input class="input" v-model="form.title"></label>
                    <label class="field"><span>نسبة الخصم</span><input class="input" type="number" min="0" max="100" step="0.01" v-model="form.discount_value" required></label>
                    <label class="field"><span>حد الفاتورة الأقصى</span><input class="input" type="number" step="0.01" v-model="form.max_invoice_amount"></label>
                    <label class="field"><span>أقصى قيمة خصم</span><input class="input" type="number" step="0.01" v-model="form.max_discount_amount"></label>
                    <label class="field"><span>بداية الصلاحية</span><input class="input" type="datetime-local" v-model="form.starts_at"></label>
                    <label class="field"><span>نهاية الصلاحية</span><input class="input" type="datetime-local" v-model="form.expires_at"></label>
                    <label class="field"><span>إجمالي الاستخدامات</span><input class="input" type="number" min="1" v-model="form.max_total_uses"></label>
                    <label class="field"><span>لكل عميل</span><input class="input" type="number" min="1" v-model="form.max_uses_per_customer" required></label>
                    <label class="field wide"><span>الوصف</span><textarea class="textarea" v-model="form.description"></textarea></label>
                    <div class="field"><span>العملاء المسموحون</span><div class="multi-list"><label v-for="c in customers" :key="c.id" class="check-row"><input type="checkbox" :value="c.id" v-model="form.customers"> {{ c.name }} - {{ c.phone }}</label></div></div>
                    <div class="field"><span>الفروع المسموحة</span><div class="multi-list"><label v-for="b in branches" :key="b.id" class="check-row"><input type="checkbox" :value="b.id" v-model="form.branches"> {{ b.name }} - {{ b.code }}</label></div></div>
                    <label class="check-row"><input type="checkbox" v-model="form.is_active"> نشط</label>
                    <div class="actions wide"><button class="btn primary" :disabled="busy">حفظ الكود</button><button type="button" class="btn" @click="clear">جديد</button></div>
                </form>
            </div>
            <div class="panel table-wrap">
                <table><thead><tr><th>الكود</th><th>الخصم</th><th>العملاء</th><th>الفروع</th><th>الاستخدامات</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody>
                    <tr v-for="row in promoCodes.data" :key="row.id"><td><strong>{{ row.code }}</strong><div class="label">{{ row.title }}</div></td><td>{{ row.discount_value }}%</td><td>{{ (row.customers || []).length }}</td><td>{{ (row.branches || []).map(b => b.name).join('، ') }}</td><td>{{ row.redemptions_count }}</td><td><span :class="['badge', row.is_active ? 'on' : 'off']">{{ row.is_active ? 'نشط' : 'متوقف' }}</span></td><td class="actions"><button class="btn" @click="pick(row)">تعديل</button><button class="btn" @click="$emit('toggle', row)">{{ row.is_active ? 'تعطيل' : 'تفعيل' }}</button></td></tr>
                    <EmptyRow v-if="!promoCodes.data?.length" :cols="7" />
                </tbody></table>
            </div>
        </section>
    `,
};

const ReportsView = {
    props: ['reports', 'customers', 'branches', 'users', 'promoCodes'],
    emits: ['filter', 'export'],
    data: () => ({ filters: { from: '', to: '', branch_id: '', customer_id: '', seller_id: '', promo_code_id: '', invoice_number: '' } }),
    computed: { rows() { return this.reports.data?.data || []; }, summary() { return this.reports.summary || {}; }, sellers() { return this.users.filter(u => u.role === 'seller'); } },
    components: { EmptyRow },
    template: `
        <section class="grid">
            <div class="cards">
                <div class="card"><div class="label">إجمالي الاستخدامات</div><div class="metric">{{ summary.total_redemptions || 0 }}</div></div>
                <div class="card"><div class="label">إجمالي الفواتير</div><div class="metric">{{ summary.total_invoice_amount || 0 }}</div></div>
                <div class="card"><div class="label">إجمالي الخصومات</div><div class="metric">{{ summary.total_discount_amount || 0 }}</div></div>
                <div class="card"><div class="label">أكثر كود</div><div class="metric" style="font-size:20px">{{ summary.top_code || '-' }}</div></div>
            </div>
            <div class="panel">
                <div class="panel-head"><h3 style="margin:0">فلاتر التقارير</h3><div class="actions"><button class="btn primary" @click="$emit('filter', filters)">تطبيق</button><button class="btn" @click="$emit('export', filters)">تصدير Excel</button></div></div>
                <div class="form">
                    <label class="field"><span>من تاريخ</span><input class="input" type="date" v-model="filters.from"></label>
                    <label class="field"><span>إلى تاريخ</span><input class="input" type="date" v-model="filters.to"></label>
                    <label class="field"><span>الفرع</span><select class="select" v-model="filters.branch_id"><option value="">الكل</option><option v-for="b in branches" :value="b.id">{{ b.name }}</option></select></label>
                    <label class="field"><span>العميل</span><select class="select" v-model="filters.customer_id"><option value="">الكل</option><option v-for="c in customers" :value="c.id">{{ c.name }}</option></select></label>
                    <label class="field"><span>البائع</span><select class="select" v-model="filters.seller_id"><option value="">الكل</option><option v-for="s in sellers" :value="s.id">{{ s.name }}</option></select></label>
                    <label class="field"><span>الكود</span><select class="select" v-model="filters.promo_code_id"><option value="">الكل</option><option v-for="p in promoCodes" :value="p.id">{{ p.code }}</option></select></label>
                    <label class="field"><span>رقم الفاتورة</span><input class="input" v-model="filters.invoice_number"></label>
                </div>
            </div>
            <div class="panel table-wrap">
                <table><thead><tr><th>الكود</th><th>العميل</th><th>الفرع</th><th>البائع</th><th>الفاتورة</th><th>الخصم</th><th>التاريخ</th></tr></thead><tbody>
                    <tr v-for="row in rows" :key="row.id"><td>{{ row.promo_code?.code }}</td><td>{{ row.customer?.name }}<div class="label">{{ row.customer?.phone }}</div></td><td>{{ row.branch?.name }}</td><td>{{ row.seller?.name }}</td><td>{{ row.invoice_number }}<div class="label">{{ row.invoice_amount }}</div></td><td>{{ row.discount_percentage }}%<div class="label">{{ row.discount_amount }}</div></td><td>{{ row.redeemed_at }}</td></tr>
                    <EmptyRow v-if="!rows.length" :cols="7" />
                </tbody></table>
            </div>
        </section>
    `,
};

const UsersView = {
    props: ['users', 'branches', 'busy'],
    emits: ['save', 'toggle', 'password', 'reload'],
    data: () => ({
        showForm: false,
        passwordUser: null,
        filters: { search: '', role: '', is_active: '', branch_id: '' },
        form: { name: '', email: '', phone: '', password: 'password', password_confirmation: 'password', role: 'seller', is_active: true, branch_ids: [] },
        passwordForm: { password: '', password_confirmation: '' },
    }),
    computed: {
        filteredUsers() {
            return this.users.filter((user) => {
                const search = this.filters.search.trim().toLowerCase();
                const matchesSearch = !search || [user.name, user.email, user.phone].some((value) => String(value || '').toLowerCase().includes(search));
                const matchesRole = !this.filters.role || user.role === this.filters.role;
                const matchesStatus = this.filters.is_active === '' || String(Boolean(user.is_active)) === this.filters.is_active;
                const matchesBranch = !this.filters.branch_id || (user.branches || []).some((branch) => String(branch.id) === String(this.filters.branch_id));
                return matchesSearch && matchesRole && matchesStatus && matchesBranch;
            });
        },
        roleOptions() {
            return [
                ['super_admin', 'Super Admin'],
                ['admin', 'Admin'],
                ['promo_manager', 'Promo Manager'],
                ['seller', 'Seller'],
                ['reports_manager', 'Reports Manager'],
            ];
        },
    },
    methods: {
        resetForm() {
            this.form = { name: '', email: '', phone: '', password: 'password', password_confirmation: 'password', role: 'seller', is_active: true, branch_ids: [] };
            this.showForm = true;
        },
        pick(user) {
            this.form = {
                id: user.id,
                name: user.name,
                email: user.email,
                phone: user.phone || '',
                password: '',
                password_confirmation: '',
                role: user.role,
                is_active: Boolean(user.is_active),
                branch_ids: (user.branches || []).map((branch) => branch.id),
            };
            this.showForm = true;
        },
        submit() {
            const payload = { ...this.form };
            if (!payload.password) {
                delete payload.password;
                delete payload.password_confirmation;
            }
            if (payload.role !== 'seller') payload.branch_ids = [];
            this.$emit('save', payload);
            this.showForm = false;
        },
        openPassword(user) {
            this.passwordUser = user;
            this.passwordForm = { password: '', password_confirmation: '' };
        },
        submitPassword() {
            this.$emit('password', { id: this.passwordUser.id, ...this.passwordForm });
            this.passwordUser = null;
        },
    },
    components: { EmptyRow },
    template: `
        <section class="grid">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h3 style="margin:0">Users Management</h3>
                        <div class="label">إدارة المستخدمين والصلاحيات والفروع المرتبطة بالبائعين</div>
                    </div>
                    <button class="btn primary" @click="resetForm">إضافة مستخدم</button>
                </div>
                <div class="form">
                    <label class="field"><span>بحث</span><input class="input" v-model="filters.search" placeholder="الاسم أو البريد أو الجوال"></label>
                    <label class="field"><span>الدور</span><select class="select" v-model="filters.role"><option value="">الكل</option><option v-for="[value, label] in roleOptions" :value="value">{{ label }}</option></select></label>
                    <label class="field"><span>الحالة</span><select class="select" v-model="filters.is_active"><option value="">الكل</option><option value="true">نشط</option><option value="false">غير نشط</option></select></label>
                    <label class="field"><span>الفرع</span><select class="select" v-model="filters.branch_id"><option value="">الكل</option><option v-for="branch in branches" :value="branch.id">{{ branch.name }}</option></select></label>
                    <div class="field"><span>&nbsp;</span><button class="btn" @click="$emit('reload')">تحديث</button></div>
                </div>
            </div>

            <div v-if="showForm" class="panel">
                <div class="panel-head">
                    <h3 style="margin:0">{{ form.id ? 'تعديل مستخدم' : 'إضافة مستخدم' }}</h3>
                    <button class="btn" @click="showForm = false">إغلاق</button>
                </div>
                <form class="form" @submit.prevent="submit">
                    <label class="field"><span>الاسم</span><input class="input" v-model="form.name" required></label>
                    <label class="field"><span>البريد الإلكتروني</span><input class="input" type="email" v-model="form.email" required></label>
                    <label class="field"><span>رقم الجوال</span><input class="input" v-model="form.phone"></label>
                    <label class="field" v-if="!form.id"><span>كلمة المرور</span><input class="input" type="password" v-model="form.password" required></label>
                    <label class="field" v-if="!form.id"><span>تأكيد كلمة المرور</span><input class="input" type="password" v-model="form.password_confirmation" required></label>
                    <label class="field"><span>الدور</span><select class="select" v-model="form.role"><option v-for="[value, label] in roleOptions" :value="value">{{ label }}</option></select></label>
                    <div class="field wide" v-if="form.role === 'seller'"><span>الفروع المرتبطة</span><div class="multi-list"><label v-for="branch in branches" :key="branch.id" class="check-row"><input type="checkbox" :value="branch.id" v-model="form.branch_ids"> {{ branch.name }} - {{ branch.code }}</label></div></div>
                    <label class="check-row"><input type="checkbox" v-model="form.is_active"> نشط</label>
                    <div class="actions wide"><button class="btn primary" :disabled="busy">حفظ</button></div>
                </form>
            </div>

            <div v-if="passwordUser" class="panel">
                <div class="panel-head"><h3 style="margin:0">تغيير كلمة مرور: {{ passwordUser.name }}</h3><button class="btn" @click="passwordUser = null">إغلاق</button></div>
                <form class="form" @submit.prevent="submitPassword">
                    <label class="field"><span>كلمة المرور الجديدة</span><input class="input" type="password" v-model="passwordForm.password" required></label>
                    <label class="field"><span>تأكيد كلمة المرور</span><input class="input" type="password" v-model="passwordForm.password_confirmation" required></label>
                    <div class="field"><span>&nbsp;</span><button class="btn blue">تغيير كلمة المرور</button></div>
                </form>
            </div>

            <div class="panel table-wrap">
                <table><thead><tr><th>الاسم</th><th>البريد</th><th>الجوال</th><th>الدور</th><th>الفروع</th><th>الحالة</th><th>تاريخ الإنشاء</th><th>إجراءات</th></tr></thead><tbody>
                    <tr v-for="u in filteredUsers" :key="u.id">
                        <td>{{ u.name }}</td>
                        <td>{{ u.email }}</td>
                        <td>{{ u.phone || '-' }}</td>
                        <td>{{ u.role }}</td>
                        <td>{{ (u.branches || []).map(b => b.name).join('، ') || '-' }}</td>
                        <td><span :class="['badge', u.is_active ? 'on' : 'off']">{{ u.is_active ? 'نشط' : 'غير نشط' }}</span></td>
                        <td>{{ u.created_at }}</td>
                        <td class="actions"><button class="btn" @click="pick(u)">تعديل</button><button class="btn" @click="$emit('toggle', u)">{{ u.is_active ? 'تعطيل' : 'تفعيل' }}</button><button class="btn" @click="openPassword(u)">كلمة المرور</button></td>
                    </tr>
                    <EmptyRow v-if="!filteredUsers.length" :cols="8" text="لا توجد مستخدمون بعد" />
                </tbody></table>
            </div>
        </section>
    `,
};

export default {
    components: { DashboardView, SellerView, CustomersView, BranchesView, PromoCodesView, ReportsView, UsersView },
};
</script>
