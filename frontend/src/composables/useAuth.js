import { ref, watch } from "vue";
import { useRouter } from "vue-router";
import api from "../api/axios";
import Swal from "sweetalert2";

const user = ref(null);
const token = ref(localStorage.getItem("auth_token") || "");
const isLoggedIn = ref(!!token.value);
const isLoaded = ref(false);

watch(token, (val) => {
    // keep localStorage in sync using the unified key "auth_token"
    if (val) localStorage.setItem("auth_token", val);
    else localStorage.removeItem("auth_token");
    isLoggedIn.value = !!val;
});

export default function useAuth() {
    const router = useRouter();

    // Fetch authenticated user
    const fetchUser = async () => {
        try {
            const { data } = await api.get("/profile");
            user.value = data;
        } catch {
            user.value = null;
        } finally {
            isLoaded.value = true;
        }
    };

    const checkAuth = async () => {
        const t = localStorage.getItem("auth_token");
        if (!t) {
            router.push("/login");
            return false;
        }
        token.value = t;
        // optionally fetch profile when token exists
        await fetchUser();
        return true;
    };

    const login = async (email, password) => {
        try {
            const response = await api.post("/authenticate", { email, password });
            if (!response.data || !response.data.token) {
                Swal.fire("Login Failed", "Invalid credentials", "error");
                return;
            }
            // persist token and user
            token.value = response.data.token;
            localStorage.setItem("auth_user", JSON.stringify(response.data.user || null));
            user.value = response.data.user || null;

            // redirect after login
            router.push("/");
        } catch (err) {
            Swal.fire("Login Failed", err?.response?.data?.message || "Invalid credentials", "error");
        }
    };

    const logout = async () => {
        clearStorage();
        token.value = "";
        user.value = null;
        router.push("/login");
    };

    const clearStorage = () => {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("auth_user");
    };

    const setToken = (t) => {
        token.value = t;
    };

    const clear = () => {
        clearStorage();
        token.value = "";
        user.value = null;
    };

    return {
        user,
        isLoaded,
        fetchUser,
        checkAuth,
        login,
        logout,
        token,
        isLoggedIn,
        setToken,
        clear,
    };
}
