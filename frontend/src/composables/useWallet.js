import { ref } from "vue";
import api from "../api/axios";

const profile = ref(null);

export function useWallet() {
    const fetchProfile = async () => {
        const { data } = await api.get<Profile>('/profile');
        profile.value = data;
    };

    return {
        profile,
        fetchProfile
    };
}
