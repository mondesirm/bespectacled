<script setup lang="ts">
import { onBeforeMount, onBeforeUnmount, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { email, maxLength, minLength, required } from '@vuelidate/validators'

import Toolbar from '@/components/common/Toolbar.vue'
import { useAuthStore, useUserShowStore, useUtilsStore } from '@/store'
import { useBreadcrumb } from '@/composables/breadcrumb'

defineProps<{ scroll: number }>()

const router = useRouter()
const breadcrumb = useBreadcrumb()
const utilsStore = useUtilsStore()

const auth = useAuthStore()
const { user, isLoading, error, violations } = storeToRefs(auth)

const store = useUserShowStore()
const { retrieved: item, isLoading: userIsLoading, error: userError } = storeToRefs(store)

const tab = ref(0)
const valid = ref(true)
const showPassword = ref(false)
const form = ref<null | typeof import('vuetify/components')['VForm']>(null)

const inputs = reactive({
	username: '',
	email: '',
	password: '',
	confirmPassword: '',
	...user?.value
})

const rules = {
	username: { required, minLength: minLength(3), maxLength: maxLength(20) },
	email: { required, email, maxLength: maxLength(50) },
	password: { required, minLength: minLength(7), maxLength: maxLength(40) },
	// sameAs validator is broken for some reason so I'm using a custom one instead
	confirmPassword: { required, sameAs: {
		$validator: (value: string) => value === inputs.password,
		$message: 'Passwords do not match'
	} }
}

const tabs = [
	{ text: 'general', 'prepend-icon': 'fa fa-info' },
	{ text: 'events', 'prepend-icon': 'fa fa-star' }
]

const icons: Record<string, string> = {
	broadway: 'fa fa-mask',
	concert: 'fa fa-microphone',
	other: 'fa fa-question'
}

const v$ = useVuelidate(rules, inputs)

const handleEditProfile = async (payload: any) => {
	if (!valid.value) return

	utilsStore.setLoading(true)

	try {
		await auth.editProfile(payload)
		if (!user?.value) return
		utilsStore.showToast('Profile updated!')
		// router.push({ name: 'login' })
		// router.push({ name: 'home' })
	} catch (err: any) {
		utilsStore.showToast('Failed to update profile!', 'danger')
	} finally {
		utilsStore.setLoading(false)
	}
}

onBeforeMount(() => !user?.value && router.push('/login'))
onBeforeUnmount(() => store.$reset())
</script>

<template>
	<v-alert v-if="error || userError" type="error" class="mb-4" v-text="error || userError" closable />

	<Toolbar color="primary-darken-1" :breadcrumb="breadcrumb" :is-loading="isLoading" main />

	<v-tabs v-model="tab" color="primary" fixed-tabs>
		<v-tab v-for="tab, i in tabs" :="tab" :value="i" />
	</v-tabs>

	<v-window v-if="user" v-model="tab" class="bg-surface-darken-1">
		<v-window-item value="0">
			<v-card :disabled="utilsStore.isLoading || !inputs">
				<v-form ref="form" v-model="valid" @submit.prevent="handleEditProfile(inputs)">
					<v-card-text>
						<v-row>
							<v-col cols="12" sm="6">
								<v-text-field
									v-model="inputs.username"
									:error="Boolean(violations?.username)"
									:error-messages="violations?.username || v$.username?.$errors.map((e: any) => e.$message)"
									:counter="20"
									label="Username*"
									required
									clearable
									@input="v$.username.$touch"
									@blur="v$.username.$touch"
								/>
							</v-col>

							<v-col cols="12" sm="6">
								<v-text-field
									v-model="inputs.email"
									:error="Boolean(violations?.email)"
									:error-messages="violations?.email || v$.email?.$errors.map((e: any) => e.$message)"
									:counter="50"
									label="Email*"
									type="email"
									required
									clearable
									@input="v$.email.$touch"
									@blur="v$.email.$touch"
								/>
							</v-col>

							<v-col cols="12" sm="6">
								<v-text-field
									v-model="inputs.password"
									:error="Boolean(violations?.password)"
									:error-messages="violations?.password || v$.password?.$errors.map((e: any) => e.$message)"
									:counter="40"
									label="Password*"
									:type="showPassword ? 'text' : 'password'"
									required
									clearable
									@input="v$.password.$touch"
									@blur="v$.password.$touch"
									:append-inner-icon="inputs.password && (showPassword ? 'fa fa-eye-slash' : 'fa fa-eye')"
									@click:append-inner="showPassword = !showPassword"
								/>
							</v-col>

							<v-col cols="12" sm="6">
								<v-text-field
									v-model="inputs.confirmPassword"
									:error="Boolean(violations?.confirmPassword)"
									:error-messages="violations?.confirmPassword || v$.confirmPassword?.$errors.map((e: any) => e.$message)"
									:counter="40"
									label="Confirm Password*"
									type="password"
									required
									clearable
									@input="v$.confirmPassword.$touch"
									@blur="v$.confirmPassword.$touch"
								/>
							</v-col>
						</v-row>
					</v-card-text>

					<v-card-actions>
						<v-spacer />

						<v-btn :disabled="!v$.$anyDirty" color="primary" @click="form?.reset()" type="reset">Reset</v-btn>
						<v-btn :loading="utilsStore.isLoading" color="primary" variant="elevated" type="submit" @click="v$.$validate">Edit Profile</v-btn>
					</v-card-actions>
				</v-form>
			</v-card>
		</v-window-item>

		<v-window-item value="1">
			<v-row v-for="event, i in user.events" :key="i" class="bg-surface-darken-1" style="min-height: 11em;">
				<v-col cols="12" sm="10" order-sm="1">
					<v-card-title class="font-title">
						<router-link v-if="user.id" :to="{ name: 'event', params: { id: event.id }}">
							{{ event.title }}
						</router-link>

						<span class="float-end text-overline text-muted">
							{{ event.type }}
							<v-icon :icon="icons[event.type]" size="md" />
						</span>
					</v-card-title>

					<v-card-text class="mb-4 pb-0 text-pre-wrap clamp-fade clamp-sm" v-text="event.description" />
				</v-col>

				<v-col cols="12" sm="2">
					<v-img :src="event.src" :alt="event.title" />
				</v-col>
			</v-row>
		</v-window-item>
	</v-window>

</template>

<style scoped>
/* .card-container.card {
	padding: 40px 40px;
}

.card {
	padding: 20px 25px 30px;
	margin: 0 auto 25px;
	margin-top: 50px;
} */
</style>