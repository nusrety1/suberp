<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Malzemeler',
        href: '/supplies',
    },
    {
        title: 'Malzeme Ekle',
        href: '/add-supply',
    },
];

const form = useForm({
    name: '',
    amount: '',
    quantity: '',
    unit: ''
});

const submitForm = () => {
    form.post(route('create-supply'), {
        onSuccess: () => {
            console.log('Malzeme başarıyla eklendi');
        },
        onError: (errors) => {
            console.log('Form hataları:', errors);
        }
    });
};

// Birim seçenekleri
const unitOptions = [
    { value: 'kg', label: 'Kilogram (kg)' },
    { value: 'gr', label: 'Gram (gr)' },
    { value: 'lt', label: 'Litre (lt)' },
    { value: 'ml', label: 'Mililitre (ml)' },
    { value: 'adet', label: 'Adet' },
    { value: 'paket', label: 'Paket' },
    { value: 'kutu', label: 'Kutu' },
    { value: 'metre', label: 'Metre (m)' },
    { value: 'cm', label: 'Santimetre (cm)' },
    { value: 'mm', label: 'Milimetre (mm)' },
    { value: 'ton', label: 'Ton' },
    { value: 'kasa', label: 'Kasa' },
    { value: 'çuval', label: 'Çuval' },
    { value: 'palet', label: 'Palet' },
    { value: 'demirbas', label: 'Demirbaş' },
    { value: 'diğer', label: 'Diğer' }
];
</script>

<template>
    <Head title="Malzeme Ekle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Sayfa Başlığı -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Yeni Malzeme Ekle
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Malzeme bilgilerini doldurun ve sisteme kaydedin.
                </p>
            </div>

            <!-- Ana Form Kartı -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
                <form @submit.prevent="submitForm" class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Malzeme Adı -->
                        <div class="space-y-2">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Malzeme Adı <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="Malzeme adı"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Toplam Ödenen Fiyat -->
                        <div class="space-y-2">
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Toplam Ödenen Fiyat <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400">₺</span>
                                </div>
                                <input
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="w-full pl-8 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                    placeholder="0.00"
                                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.amount }"
                                />
                            </div>
                            <p v-if="form.errors.amount" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.amount }}
                            </p>
                        </div>

                        <!-- Miktar -->
                        <div class="space-y-2">
                            <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Miktar <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="quantity"
                                v-model="form.quantity"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="0"
                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.quantity }"
                            />
                            <p v-if="form.errors.quantity" class="text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.quantity }}
                            </p>
                        </div>
                    </div>

                    <!-- Birim - Tam genişlik -->
                    <div class="mt-6 space-y-2">
                        <label for="unit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Birim <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="unit"
                            v-model="form.unit"
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': form.errors.unit }"
                        >
                            <option value="">Birim seçin</option>
                            <option v-for="option in unitOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.unit" class="text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.unit }}
                        </p>
                    </div>

                    <!-- Özet Bilgisi -->
                    <div v-if="form.name && form.quantity && form.amount && form.unit" class="mt-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200 mb-2">
                            Malzeme Özeti
                        </h3>
                        <div class="text-sm text-green-700 dark:text-green-300 space-y-1">
                            <p><strong>Malzeme:</strong> {{ form.name }}</p>
                            <p><strong>Miktar:</strong> {{ form.quantity }} {{ form.unit }}</p>
                            <p><strong>Toplam Ödenen Fiyat:</strong> {{ parseFloat(form.amount).toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' }) }}</p>
                        </div>
                    </div>

                    <!-- Form Butonları -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-4 sm:justify-end">
                        <button
                            type="button"
                            @click="$inertia.visit('/supplies')"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
                        >
                            İptal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 flex items-center justify-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Kaydediliyor...' : 'Malzeme Ekle' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bilgi Notu -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Bilgi
                        </h3>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            Zorunlu alanlar (*) işaretli olanlardır.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
