<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PurchaseDetail from '@/components/PurchaseDetail.vue';
import SupplyPaymentModal from '@/components/SupplyPaymentModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import type { BreadcrumbItem } from '@/types';

interface Product {
    id: number;
    name: string;
    price: number;
    slug: string;
}

interface PurchaseProduct {
    id: number;
    quantity: number;
    purchase_time_unit_price: number;
    product: Product;
}

interface Purchase {
    id: number;
    customer_id: number;
    repayment_date: string;
    bargain_price: number;
    payment_method: string;
    paid_amount: number;
    description?: string;
    created_at: string;
    updated_at: string;
    products: PurchaseProduct[];
    // Backend tarafından eklendi: bu satış için ödenen toplam ürün adedi
    paid_product_quantity?: number;
}

interface Customer {
    id: number;
    name: string;
    surname: string;
    full_name: string;
    email: string;
    phone: string;
    address: string;
    description?: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedPurchases {
    data: Purchase[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url?: string;
    prev_page_url?: string;
}

interface ProductBasedSale {
    product_id: number;
    product_name: string;
    total_quantity: number;
    current_price: number;
    total_current_value: number;
    total_paid_amount: number;
    paid_product_quantity: number;
    total_bargain_discount: number; // Toplam pazarlık indirimi
    remaining_debt: number;
    showDetails?: boolean;
    purchases: {
        purchase_id: number;
        purchase_date: string;
        quantity: number;
        purchase_time_price: number;
        current_price: number;
        purchase_time_total: number;
        current_total: number;
        paid_amount: number;
        paid_quantity: number;
        bargain_discount: number; // Satış başına pazarlık indirimi
    }[];
}

interface Supply {
    id: number;
    name: string;
    slug: string;
    amount: number;
    paid_amount: number;
    quantity: number;
    unit: string;
    description: string;
    customer_id: number | null;
    created_at: string;
    updated_at: string;
    customer: Customer | null;
}

interface PaginatedSupplies {
    data: Supply[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url?: string;
    prev_page_url?: string;
}

interface Props {
    customer: Customer;
    purchases: PaginatedPurchases;
    totalDebt: number;
    totalPaid: number;
    remainingDebt: number;
    totalBargainDiscount: number;
    productBasedSales: ProductBasedSale[];
    supplies: PaginatedSupplies;
    filters: {
        date_from?: string;
        date_to?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Müşteriler',
        href: '/customers',
    },
    {
        title: props.customer.full_name,
        href: `/customer/${props.customer.id}/details`,
    },
];

// Tab yönetimi
const activeTab = ref('product-based'); // 'purchases', 'product-based', 'supplies' veya 'payments'

const switchTab = (tab: string) => {
    activeTab.value = tab;
    if (tab === 'payments') {
        fetchPaymentHistory();
    }
};

// Filtreleme state'i
const searchTerm = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// Modal state
const isModalOpen = ref(false);
const selectedPurchase = ref<Purchase | null>(null);

// Supply payment modal state
const isSupplyPaymentModalOpen = ref(false);
const selectedSupply = ref<Supply | null>(null);

// Payment history state
const paymentHistory = ref<any[]>([]);
const paymentHistoryLoading = ref(false);
const paymentHistoryError = ref<string | null>(null);
const paymentHistoryTotal = ref(0);

// Filtreleme fonksiyonu
const applyFilters = () => {
    const params: any = {};

    if (searchTerm.value) params.search = searchTerm.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    router.get(`/customer/${props.customer.id}/details`, params, {
        preserveState: true,
        replace: true
    });
};

// Filtreleri temizle
const clearFilters = () => {
    searchTerm.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get(`/customer/${props.customer.id}/details`, {}, {
        preserveState: true,
        replace: true
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
};

const formatQuantity = (quantity: number, unit: string) => {
    return `${quantity} ${unit}`;
};

// Güncel fiyatlarla toplam hesaplama (pazarlık indirimi ile)
const calculateCurrentTotal = (purchase: Purchase) => {
    const productTotal = purchase.products.reduce((total, item) => {
        return total + (item.product.price * item.quantity);
    }, 0);

    // Pazarlık indirimini çıkar
    return productTotal - purchase.bargain_price;
};

// Satış zamanındaki fiyatlarla toplam hesaplama
const calculatePurchaseTimeTotal = (purchase: Purchase) => {
    return purchase.products.reduce((total, item) => {
        return total + (item.purchase_time_unit_price * item.quantity);
    }, 0);
};

// Modal fonksiyonları
const openPurchaseDetail = (purchase: Purchase) => {
    selectedPurchase.value = purchase;
    isModalOpen.value = true;
};

const closePurchaseDetail = () => {
    isModalOpen.value = false;
    selectedPurchase.value = null;
};

// Supply payment modal functions
const openSupplyPaymentModal = (supply: Supply) => {
    selectedSupply.value = supply;
    isSupplyPaymentModalOpen.value = true;
};

const closeSupplyPaymentModal = () => {
    isSupplyPaymentModalOpen.value = false;
    selectedSupply.value = null;
};

// Ödeme geçmişi fonksiyonu
const fetchPaymentHistory = async () => {
    paymentHistoryLoading.value = true;
    paymentHistoryError.value = null;

    try {
        const params: any = {};
        if (dateFrom.value) params.date_from = dateFrom.value;
        if (dateTo.value) params.date_to = dateTo.value;
        if (searchTerm.value) params.search = searchTerm.value;

        const response = await axios.get(`/customer/${props.customer.id}/payment-history`, { params });

        paymentHistory.value = response.data.payments.data;
        paymentHistoryTotal.value = response.data.totalPaidAmount;
    } catch (error) {
        paymentHistoryError.value = 'Ödeme geçmişi yüklenirken bir hata oluştu.';
        console.error('Payment history error:', error);
    } finally {
        paymentHistoryLoading.value = false;
    }
};
</script>

<template>
    <Head :title="`${props.customer.full_name} - Müşteri Detayı`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Müşteri Bilgileri -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0 h-16 w-16">
                                <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-2xl font-medium text-blue-600">
                                        {{ props.customer.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ props.customer.full_name }}</h1>
                                <p class="text-gray-600">ID: #{{ props.customer.id }}</p>
                            </div>
                        </div>
                        <button
                            @click="$inertia.visit('/customers')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Geri Dön
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">E-posta</h3>
                            <p class="mt-1 text-sm text-gray-900">{{ props.customer.email }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Telefon</h3>
                            <p class="mt-1 text-sm text-gray-900">{{ props.customer.phone || 'Belirtilmemiş' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Adres</h3>
                            <p class="mt-1 text-sm text-gray-900">{{ props.customer.address || 'Belirtilmemiş' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Not</h3>
                            <p class="mt-1 text-sm text-gray-900">{{ props.customer.description || 'Belirtilmemiş' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finansal Özet -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-100 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Toplam Borç (Pazarlık İndirimi Sonrası)</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(props.totalDebt) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Ödenen Miktar</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(props.totalPaid) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-100 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Kalan Borç</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(props.remainingDebt) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Toplam Pazarlık İndirimi</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(props.totalBargainDiscount) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Menüsü -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
<!--                        <button-->
<!--                            @click="switchTab('purchases')"-->
<!--                            :class="{-->
<!--                                'border-blue-500 text-blue-600': activeTab === 'purchases',-->
<!--                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'purchases'-->
<!--                            }"-->
<!--                            class="py-4 px-6 border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200"-->
<!--                        >-->
<!--                            Satış Geçmişi-->
<!--                        </button>-->
                        <button
                            @click="switchTab('product-based')"
                            :class="{
                                'border-blue-500 text-blue-600': activeTab === 'product-based',
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'product-based'
                            }"
                            class="py-4 px-6 border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200"
                        >
                            Ürün Bazlı Satışlar
                        </button>
                        <button
                            @click="switchTab('supplies')"
                            :class="{
                                'border-blue-500 text-blue-600': activeTab === 'supplies',
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'supplies'
                            }"
                            class="py-4 px-6 border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200"
                        >
                            Malzeme Alışları
                        </button>
                        <button
                            @click="switchTab('payments')"
                            :class="{
                                'border-blue-500 text-blue-600': activeTab === 'payments',
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'payments'
                            }"
                            class="py-4 px-6 border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200"
                        >
                            Ödeme Geçmişi
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Satış Geçmişi Tab İçeriği -->
            <div v-if="activeTab === 'purchases'" class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Satış Geçmişi</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Arama</label>
                            <input
                                v-model="searchTerm"
                                type="text"
                                placeholder="Satış ID veya açıklama..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Başlangıç Tarihi</label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bitiş Tarihi</label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div class="flex items-end space-x-2">
                            <button
                                @click="applyFilters"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200"
                            >
                                Filtrele
                            </button>
                            <button
                                @click="clearFilters"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md transition-colors duration-200"
                            >
                                Temizle
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Satış Listesi -->
            <div v-if="activeTab === 'purchases'" class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Satış ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tarih
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ürünler
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Açıklama
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Satış Zamanı Fiyatı
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Güncel Fiyat (Pazarlık Sonrası)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ödenen
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ödenen Adet
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kalan
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="purchase in props.purchases.data" :key="purchase.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#{{ purchase.id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(purchase.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div v-for="item in purchase.products" :key="item.id" class="text-sm">
                                            <div class="font-medium text-gray-900">{{ item.product.name }}</div>
                                            <div class="text-gray-500">{{ item.quantity }} adet</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    {{ purchase.description ? (purchase.description.length > 50 ? purchase.description.substring(0, 50) + '...' : purchase.description) : '' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(calculatePurchaseTimeTotal(purchase)) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(calculateCurrentTotal(purchase)) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(purchase.paid_amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ purchase.paid_product_quantity ?? 0 }} adet
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(calculateCurrentTotal(purchase) - purchase.paid_amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        @click="openPurchaseDetail(purchase)"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium rounded-md transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Detay
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6" v-if="props.purchases.last_page > 1">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button
                            v-if="props.purchases.prev_page_url"
                            @click="$inertia.visit(props.purchases.prev_page_url)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Önceki
                        </button>
                        <button
                            v-if="props.purchases.next_page_url"
                            @click="$inertia.visit(props.purchases.next_page_url)"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Sonraki
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Toplam <span class="font-medium">{{ props.purchases.total }}</span> sonuçtan
                                <span class="font-medium">{{ ((props.purchases.current_page - 1) * props.purchases.per_page) + 1 }}</span>
                                -
                                <span class="font-medium">{{ Math.min(props.purchases.current_page * props.purchases.per_page, props.purchases.total) }}</span>
                                arası gösteriliyor
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button
                                    v-if="props.purchases.prev_page_url"
                                    @click="$inertia.visit(props.purchases.prev_page_url)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                    {{ props.purchases.current_page }} / {{ props.purchases.last_page }}
                                </span>

                                <button
                                    v-if="props.purchases.next_page_url"
                                    @click="$inertia.visit(props.purchases.next_page_url)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="props.purchases.data.length === 0 && activeTab === 'purchases'" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz satış yok</h3>
                <p class="mt-1 text-sm text-gray-500">Bu müşteri için henüz satış kaydı bulunmuyor.</p>
            </div>

            <!-- Ürün Bazlı Satışlar Tab İçeriği -->
            <div v-if="activeTab === 'product-based'" class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Ürün Bazlı Satışlar</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Başlangıç Tarihi</label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bitiş Tarihi</label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="applyFilters"
                                class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Filtrele
                            </button>
                            <button
                                @click="clearFilters"
                                class="ml-2 px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                            >
                                Temizle
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ürün Adı
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Toplam Miktar
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Güncel Birim Fiyat
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Toplam Güncel Değer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Toplam Ödenen
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Toplam Ödenen Adet
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pazarlık İndirimi
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kalan Borç
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Detay
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="productSale in props.productBasedSales" :key="productSale.product_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ productSale.product_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ productSale.total_quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(productSale.current_price) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(productSale.total_current_value) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(productSale.total_paid_amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ productSale.paid_product_quantity ?? 0 }} adet
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                    {{ formatCurrency(productSale.total_bargain_discount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" :class="{'text-red-600': productSale.remaining_debt > 0, 'text-green-600': productSale.remaining_debt <= 0}">
                                    {{ formatCurrency(productSale.remaining_debt) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        @click="productSale.showDetails = !productSale.showDetails"
                                        class="text-blue-600 hover:text-blue-900 focus:outline-none"
                                    >
                                        {{ productSale.showDetails ? 'Gizle' : 'Göster' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="props.productBasedSales.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Bu müşteri için henüz ürün satışı bulunmuyor.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Ürün Detayları -->
                <div v-for="productSale in props.productBasedSales" :key="'detail-' + productSale.product_id">
                    <div v-if="productSale.showDetails" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <h4 class="text-md font-medium text-gray-900 mb-3">{{ productSale.product_name }} - Satış Detayları</h4>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Satış ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tarih
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Miktar
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Satış Zamanı Fiyatı
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Güncel Fiyat
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Satış Zamanı Toplam
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Güncel Toplam
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Pazarlık İndirimi
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ödenen Miktar
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ödenen Adet
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="purchase in productSale.purchases" :key="purchase.purchase_id + '-' + productSale.product_id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        #{{ purchase.purchase_id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(purchase.purchase_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ purchase.quantity }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCurrency(purchase.purchase_time_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCurrency(purchase.current_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCurrency(purchase.purchase_time_total) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCurrency(purchase.current_total) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        {{ formatCurrency(purchase.bargain_discount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCurrency(purchase.paid_amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ purchase.paid_quantity ?? 0 }} adet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Malzeme Alışları Tab İçeriği -->
            <div v-if="activeTab === 'supplies'" class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Malzeme Alışları</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Malzeme
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Miktar
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Toplam Değer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Toplam Ödenen Tutar
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Açıklama
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Alış Tarihi
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kalan Borç
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="supply in props.supplies.data" :key="supply.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-green-600">
                                                    {{ supply.name.charAt(0).toUpperCase() }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ supply.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ID: #{{ supply.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ formatQuantity(supply.quantity, supply.unit) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(supply.amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(supply.paid_amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ supply.description ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(supply.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="(supply.amount - supply.paid_amount) > 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ formatCurrency(supply.amount - supply.paid_amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        @click="openSupplyPaymentModal(supply)"
                                        :disabled="(supply.amount - supply.paid_amount) <= 0"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 font-medium rounded-md transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        Ödeme
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6" v-if="props.supplies.last_page > 1">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button
                            v-if="props.supplies.prev_page_url"
                            @click="$inertia.visit(props.supplies.prev_page_url)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Önceki
                        </button>
                        <button
                            v-if="props.supplies.next_page_url"
                            @click="$inertia.visit(props.supplies.next_page_url)"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Sonraki
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Toplam <span class="font-medium">{{ props.supplies.total }}</span> sonuçtan
                                <span class="font-medium">{{ ((props.supplies.current_page - 1) * props.supplies.per_page) + 1 }}</span>
                                -
                                <span class="font-medium">{{ Math.min(props.supplies.current_page * props.supplies.per_page, props.supplies.total) }}</span>
                                arası gösteriliyor
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button
                                    v-if="props.supplies.prev_page_url"
                                    @click="$inertia.visit(props.supplies.prev_page_url)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                    {{ props.supplies.current_page }} / {{ props.supplies.last_page }}
                                </span>

                                <button
                                    v-if="props.supplies.next_page_url"
                                    @click="$inertia.visit(props.supplies.next_page_url)"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="props.supplies.data.length === 0 && activeTab === 'supplies'" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz malzeme alışı yok</h3>
                <p class="mt-1 text-sm text-gray-500">Bu müşteriden henüz malzeme alışı yapılmamış.</p>
            </div>

            <!-- Ödeme Geçmişi Tab İçeriği -->
            <div v-if="activeTab === 'payments'" class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Ödeme Geçmişi</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Arama</label>
                            <input
                                v-model="searchTerm"
                                type="text"
                                placeholder="Ürün adı veya açıklama..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                @keyup.enter="fetchPaymentHistory"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Başlangıç Tarihi</label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bitiş Tarihi</label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div class="flex items-end space-x-2">
                            <button
                                @click="fetchPaymentHistory"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200"
                            >
                                Filtrele
                            </button>
                            <button
                                @click="clearFilters"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md transition-colors duration-200"
                            >
                                Temizle
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="paymentHistoryLoading" class="px-6 py-8 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-2 text-sm text-gray-500">Ödeme geçmişi yükleniyor...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="paymentHistoryError" class="px-6 py-8 text-center">
                    <div class="text-red-600 mb-2">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500">{{ paymentHistoryError }}</p>
                </div>

                <!-- Payment History Table -->
                <div v-else-if="paymentHistory.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ödeme ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Satış ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ürün
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ürün Adedi
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ödenen Tutar
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Açıklama
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ödeme Tarihi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="payment in paymentHistory" :key="payment.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#{{ payment.id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#{{ payment.purchase_id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ payment.product?.name || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ payment.product_quantity || 'N/A' }} adet
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ formatCurrency(payment.paid_amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate">
                                        {{ payment.description || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(payment.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz ödeme yok</h3>
                    <p class="mt-1 text-sm text-gray-500">Bu müşteri için henüz ödeme kaydı bulunmuyor.</p>
                </div>

                <!-- Toplam Ödeme Özeti -->
                <div v-if="paymentHistory.length > 0" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-medium text-gray-900">Toplam Ödenen Tutar:</span>
                        <span class="text-xl font-bold text-green-600">{{ formatCurrency(paymentHistoryTotal) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Detail Modal -->
        <PurchaseDetail
            v-if="selectedPurchase"
            :purchase-id="selectedPurchase.id"
            :is-open="isModalOpen"
            @close="closePurchaseDetail"
        />

        <!-- Supply Payment Modal -->
        <SupplyPaymentModal
            :supply="selectedSupply"
            :is-open="isSupplyPaymentModalOpen"
            :customer-total-debt="props.remainingDebt"
            @close="closeSupplyPaymentModal"
        />
    </AppLayout>
</template>
