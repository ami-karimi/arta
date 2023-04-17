<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { Table } from "@protonemedia/inertiajs-tables-laravel-query-builder";
defineProps(["ras"])



</script>

<template>
    <Head title="RasList" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rass List</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 min-h-screen">
                    <div class="text-right">


                        <button   class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                            ایجاد جدید
                        </button>

                    </div>
                    <Table :resource="ras" >
                        <template #cell(actions)="{ item: user }">
                            <a :href="`/users/${user.id}/edit`">
                                Edit
                            </a>
                        </template>
                    </Table>
                </div>
            </div>
        </div>

        <!-- Main modal -->

    </AuthenticatedLayout>
</template>
