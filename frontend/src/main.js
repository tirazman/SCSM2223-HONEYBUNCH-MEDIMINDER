import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import App from './App.vue'
import router from './router' // Connects your routing
import './style.css' // Your global styles

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)   

app.use(router)
app.use(Toast, {
  position: 'top-right',
  timeout: 4000,
  closeOnClick: true,
})
app.mount('#app')