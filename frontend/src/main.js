import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router' // Connects your routing
import './style.css' // Your global styles

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)   

app.use(router)
app.mount('#app')