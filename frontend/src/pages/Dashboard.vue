<template>
    <div>
        <NavBar />
        <div class="max-w-4xl mx-auto mt-8 px-4">
            <h1 class="text-3xl font-bold mb-4">Dashboard</h1>
            <p class="text-gray-700">Welcome to your dashboard. This view is available only when logged in.</p>

            <!-- placeholder content -->
            <div class="mt-6 bg-white shadow rounded p-4">
                <p class="text-sm text-gray-600">Protected content goes here.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import NavBar from "../components/NavBar.vue";
import useAuth from "../composables/useAuth";

const router = useRouter();
const { isLoggedIn } = useAuth();

onMounted(() => {
    if (!isLoggedIn.value) {
        router.push("/login");
    }
});
</script>

<style scoped>
/* small styling if needed */
</style>
