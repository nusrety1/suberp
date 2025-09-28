<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PurchaseDetail from '@/components/PurchaseDetail.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface Customer {
    id: number;
    name: string;
    email: string;
    phone: string;
    address: string;
    description?: string;
    created_at: string;
    updated_at: string;
}

interface Purchase {
    id: number;
    customer_id: number;
    repayment_date: string;
    bargain_price: number;
    created_at: string;
    updated_at: string;
    customer: Customer;
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
    purchases: PaginatedPurchases;
}

const props = defineProps<Props>();

// Modal state
const isModalOpen = ref(false);
const selectedPurchase = ref<Purchase | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Satın Alımlar',
        href: '/purchases',
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(price);
};

const getStatusColor = (repaymentDate: string) => {
    const today = new Date();
    const repayment = new Date(repaymentDate);

    if (repayment < today) {
        return 'bg-red-100 text-red-800';
    } else if (repayment.getTime() - today.getTime() < 7 * 24 * 60 * 60 * 1000) {
        return 'bg-yellow-100 text-yellow-800';
    } else {
        return 'bg-green-100 text-green-800';
    }
};

const getStatusText = (repaymentDate: string) => {
    const today = new Date();
    const repayment = new Date(repaymentDate);

    if (repayment < today) {
        return 'Gecikmiş';
    } else if (repayment.getTime() - today.getTime() < 7 * 24 * 60 * 60 * 1000) {
        return 'Yaklaşan';
    } else {
        return 'Aktif';
    }
};

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
    <Head title="Satın Alımlar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Satın Alımlar</h1>
                    <p class="text-gray-600 mt-1">Toplam {{ props.purchases.total }} satın alım</p>
                </div>
                <button
                    @click="$inertia.visit('/add-purchase')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Yeni Satış Yap
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Müşteri
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pazarlık Fiyatı
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Geri Ödeme Tarihi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Durum
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kayıt Tarihi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="purchase in props.purchases.data" :key="purchase.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-blue-600">
                                                    {{ purchase.customer.name.charAt(0).toUpperCase() }}
                                                </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ purchase.customer.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ purchase.customer.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ formatPrice(purchase.bargain_price) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(purchase.repayment_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusColor(purchase.repayment_date)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ getStatusText(purchase.repayment_date) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(purchase.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button
                                    @click="openPurchaseDetail(purchase)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 3H3m4 10v6a1 1 0 001 1h9a1 1 0 001-1v-6m-10 0V9a1 1 0 011-1h8a1 1 0 011 1v4.01M9 19v-6h6v6" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Henüz satın alım yok</h3>
                <p class="mt-1 text-sm text-gray-500">İlk satışınızı kaydederek başlayın.</p>
                <div class="mt-6">
                    <button
                        @click="$inertia.visit('/add-purchase')"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Yeni Satış Yap
                    </button>
                </div>
            </div>
        </div>

        <PurchaseDetail
            v-if="selectedPurchase"
            :purchase-id="selectedPurchase.id"
            :is-open="isModalOpen"
            @close="closePurchaseDetail"
        />
    </AppLayout>
</template>
