<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Package } from 'lucide-vue-next';
import { computed } from 'vue';

// ✅ Props düzgün şekilde alınıyor
const props = defineProps<{
    product?: {
        id: number;
        name: string;
        price: number;
        old_price?: number;
        description?: string;
    };
    is_edit?: boolean;
}>();

// isEdit durumu
const isEdit = computed(() => !!props.is_edit);

// Form setup (props.product varsa onları kullan)
const form = useForm({
    id: props.product?.id ?? null,
    name: props.product?.name ?? '',
    price: props.product?.price ?? 0,
    old_price: props.product?.old_price ?? 0,
    description: props.product?.description ?? '',
});

// Submit işlemi
const submit = () => {
    if (isEdit.value) {
        form.put(route('update-product'), {
            onSuccess: () => {
                console.log('Ürün başarıyla güncellendi');
            },
            onError: (errors) => {
                console.error('Güncelleme hatası:', errors);
            },
        });
    } else {
        form.post(route('create-product'), {
            onSuccess: () => {
                console.log('Ürün başarıyla eklendi');
                form.reset();
            },
            onError: (errors) => {
                console.error('Ekleme hatası:', errors);
            },
        });
    }
};

const breadcrumbs = [
    { title: 'Ürünler', href: '/products' },
    { title: props.is_edit ? 'Ürün Güncelle' : 'Ürün Ekle', href: '/products/create' },
];

</script>

<template>
    <Head :title="isEdit ? 'Ürün Güncelle' : 'Ürün Ekle'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto py-8">
            <Card class="shadow-lg">
                <CardHeader class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b">
                    <div class="flex items-center gap-3 mt-6">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <Package class="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <CardTitle class="text-2xl font-bold text-gray-900">
                                {{ isEdit ? 'Ürünü Güncelle' : 'Yeni Ürün Ekle' }}
                            </CardTitle>
                            <CardDescription class="text-gray-600 mt-1">
                                {{ isEdit
                                ? 'Ürün bilgilerini güncellemek için aşağıdaki alanları düzenleyin.'
                                : 'Envanterinize yeni bir ürün eklemek için aşağıdaki bilgileri doldurun.' }}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-8">
                    <form @submit.prevent="submit" class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="name" class="text-sm font-semibold text-gray-700">
                                    Ürün Adı <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    v-model="form.name"
                                    placeholder="Ürün adını girin"
                                    class="h-11"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="space-y-2">
                                <Label for="price" class="text-sm font-semibold text-gray-700">
                                    Fiyat (₺) <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    :tabindex="2"
                                    v-model="form.price"
                                    placeholder="0.00"
                                    class="h-11"
                                />
                                <InputError :message="form.errors.price" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description" class="text-sm font-semibold text-gray-700">
                                Ürün Açıklaması
                            </Label>
                            <input
                                id="description"
                                type="text"
                                :tabindex="3"
                                v-model="form.description"
                                placeholder="Ürününüzü detaylı bir şekilde açıklayın..."
                                class="resize-none h-11 w-full border border-gray-300 rounded px-3"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t">
                            <Button
                                type="submit"
                                class="flex-1 h-12 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 font-semibold"
                                :tabindex="4"
                                :disabled="form.processing"
                            >
                                <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                                <Package v-else class="h-5 w-5 mr-2" />
                                {{ form.processing
                                ? (isEdit ? 'Güncelleniyor...' : 'Ürün Ekleniyor...')
                                : (isEdit ? 'Güncelle' : 'Ürün Ekle') }}
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1 h-12 font-semibold"
                                :tabindex="5"
                                @click="form.reset()"
                            >
                                Formu Temizle
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
