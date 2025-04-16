import axios from 'axios';

const axiosClient = axios.create({
    baseURL: 'http://localhost:8000', // Adresse du backend Laravel
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    },
    withCredentials: true   // Permet d'envoyer les cookies (nécessaire pour Sanctum)
});

// Request interceptor
axiosClient.interceptors.request.use((config) => {
    return config;
});

// Response interceptor
axiosClient.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('user');
            // Handle unauthorized access
        }
        
        return Promise.reject(error);
    }
);

export default axiosClient;