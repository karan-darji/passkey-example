<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import OtherAuthButton from '@/Components/OtherAuthButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref,onMounted } from 'vue';
import { browserSupportsWebAuthn, startAuthentication } from "@simplewebauthn/browser";


defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
const showPasswordField = ref(!browserSupportsWebAuthn());

const form = useForm({
    email: '',
    password: '',
    remember: false,
    answer:''
});


const authenticate = async (manualSubmission=false) => {

    if (showPasswordField.value) {
        return submit()
    }
    let answer;
    try {
        const options = await axios.get('/api/passkeys/authenticate', {
            params: { email: form.email },
        });
        console.log('option', options)
        answer = await startAuthentication(options.data);
        console.log('answer', answer)
    } catch (e) {
        console.log('error',e)
        if (manualSubmission) {
            showPasswordField.value = true;
        }
        return;
    }
    console.log('answer11', answer)
    form.action = '/passkeys/authenticate';
    form.answer = JSON.stringify(answer)
    console.log('form',form)
    form.post(route('passkeys.authenticate'));
}

onMounted(()=> {
    authenticate();
});

const submit = async () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>

        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="authenticate(true)">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autofocus
                    autocomplete="username" />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4" v-if="showPasswordField">
                <InputLabel for="password" value="Password" />

                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required
                    autocomplete="current-password" />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link v-if="canResetPassword" :href="route('password.request')"
                    class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                Forgot your password?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>
        <div>
            <p class="text-center mt-6 font-semibold dark:text-gray-200">OR</p>
            <OtherAuthButton v-show="!showPasswordField" @click="showPasswordField=!showPasswordField">Sign in with a password</OtherAuthButton>
            <OtherAuthButton v-show="showPasswordField" @click="showPasswordField=!showPasswordField">Sign in with a passkey</OtherAuthButton>
        </div>
    </GuestLayout>
</template>
