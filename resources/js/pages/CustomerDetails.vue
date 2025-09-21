<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PurchaseDetail from '@/components/PurchaseDetail.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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

interface Props {
    customer: Customer;
    purchases: PaginatedPurchases;
    totalDebt: number;
    totalPaid: number;
    remainingDebt: number;
    totalBargainDiscount: number;
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

// Filtreleme state'i
const searchTerm = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

// Modal state
const isModalOpen = ref(false);
const selectedPurchase = ref<Purchase | null>(null);

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

            <!-- Filtreleme -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
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
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
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
                                    Satış Zamanı Fiyatı
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Güncel Fiyat (Pazarlık Sonrası)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ödenen
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
                                    <div class="text-sm text-gray-500" v-if="purchase.description">
                                        {{ purchase.description }}
                                    </div>
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

            <div v-if="props.purchases.data.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz satış yok</h3>
                <p class="mt-1 text-sm text-gray-500">Bu müşteri için henüz satış kaydı bulunmuyor.</p>
            </div>
        </div>

        <!-- Purchase Detail Modal -->
        <PurchaseDetail
            v-if="selectedPurchase"
            :purchase-id="selectedPurchase.id"
            :is-open="isModalOpen"
            @close="closePurchaseDetail"
        />
    </AppLayout>
</template>
