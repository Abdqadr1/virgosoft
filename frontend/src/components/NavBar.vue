<template>
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-4">
                    <router-link to="/" class="text-xl font-bold text-indigo-600">VirgoSoft</router-link>
                    <router-link v-if="isLoggedIn" to="/dashboard"
                        class="text-gray-600 hover:text-indigo-600">Dashboard</router-link>
                </div>

                <div>
                    <button v-if="isLoggedIn" @click="logout"
                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                        Logout
                    </button>

                    <router-link v-else to="/login"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Login
                    </router-link>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { useRouter } from "vue-router";
import useAuth from "../composables/useAuth";

const { isLoggedIn, clear } = useAuth();
const router = useRouter();

const logout = () => {
    clear();
    router.push("/login");
};
</script>

<style scoped>
/* small visual tweaks */
</style>
