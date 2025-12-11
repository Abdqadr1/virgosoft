import { ref } from "vue";
import api from "../api/axios";

const tokens = ref([]);

export function useTokens() {
    const fetchProfile = async () => {
        const { data } = await api.get('/profile');
        profile.value = data;
    };

    return {
        profile,
        fetchProfile
    };
}
