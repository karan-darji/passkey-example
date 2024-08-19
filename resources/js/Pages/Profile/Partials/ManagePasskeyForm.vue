<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { browserSupportsWebAuthn, startRegistration } from "@simplewebauthn/browser";


dayjs.extend(relativeTime);

const passkeyNameInput = ref(null);

defineProps({
    passkeys: {
        type: Object,
    }
});

const form = useForm({
    name: '',
    passkey:''
});
const deleteForm = useForm({});

const createPasskey = async () => {
    if (!browserSupportsWebAuthn()) {
        return;
    }
    const options = await axios.get('/api/passkeys/register',{
        params: { name: form.name },
        validateStatus: (status) => [200, 422].includes(status),
    });
    if (options.status === 422) {
        form.errors.name = options.data.errors;
        return;
    }

    try {
        const passkey = await startRegistration(options.data);
        form.passkey = JSON.stringify(passkey)
    } catch (e) {
        form.errors.name = 'Passkey creation failed. Please try again.'
        return;
    }
    form.post(route('passkeys.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.name) {
                form.reset('name');
                passkeyNameInput.value.focus();
            }
        },
    });
};

const deletePasskey = (passkey) => {
    deleteForm.delete(route('passkeys.destroy', passkey), {
        preserveScroll: true,
        onSuccess: () => { },
    });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Manage Passkeys
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Passkeys allow for a more secure, seamless authentication experience on
                supported devices.
            </p>
        </header>

        <form @submit.prevent="createPasskey" class="mt-6 space-y-6" v-show="browserSupportsWebAuthn()">
            <div>
                <InputLabel for="passkey_name" value="Passkey Name" />

                <TextInput id="passkey_name" ref="passkeyNameInput" v-model="form.name" type="text"
                    class="mt-1 block w-full" required autocomplete="name" />

                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Create Passkey</PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">
                        Created.
                    </p>
                </Transition>
            </div>
        </form>

        <div class="mt-6">
            <h3 class="font-medium text-gray-900 dark:text-gray-200">Your Passkeys</h3>
            <ul class="mt-2">
                <li v-for="passkey in passkeys" :key="passkey.id" class="px-2 py-2 flex justify-between items-center">
                    <div class="flex flex-col">
                        <span class="font-semibold dark:text-gray-200">{{ passkey.name }}</span>
                        <span class="font-thin text-sm text-gray-600">Added {{ dayjs(passkey.created_at).fromNow()
                            }}</span>
                    </div>

                    <DangerButton @click="deletePasskey(passkey)">Remove</DangerButton>
                </li>
            </ul>
        </div>
    </section>
</template>
