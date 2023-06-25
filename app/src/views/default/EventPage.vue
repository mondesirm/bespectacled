<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'

import { Event } from '@/types/event'
import Toolbar from '@/components/common/Toolbar.vue'
import { useBreadcrumb } from '@/composables/breadcrumb'
import { useMercureItem } from '@/composables/mercureItem'
import { useEventDeleteStore, useEventShowStore, useUtilsStore } from '@/store'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = useUtilsStore()
const breadcrumb = useBreadcrumb()

const deleteStore = useEventDeleteStore()
const { deleted, error: deleteError } = storeToRefs(deleteStore)

const store = useEventShowStore()
const { retrieved: item, isLoading, error } = storeToRefs(store)

const tab = ref(2)
const menus = ref<{ venue: boolean, days: boolean[], times: Record<string, boolean> }>({ venue: false, days: [], times: {} })

const general: (keyof Event)[] = ['title', 'type', 'price']
const tabs = [
	{ text: 'venue', 'prepend-icon': 'fa fa-location-dot' },
	{ text: 'artists', 'prepend-icon': 'fa fa-users' },
	{ text: 'schedules', 'prepend-icon': 'fa fa-calendar-alt' }
]

const options: { [key: string]: Intl.DateTimeFormatOptions } = {
	weekday: { weekday: 'long' },
	short: { month: 'short', day: 'numeric' },
	long: { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
}

useMercureItem({ store, deleteStore, redirectRouteName: 'events' })

await store.retrieve(decodeURIComponent(route.params.id as string))

onBeforeUnmount(() => store.$reset())
</script>

<template>
	<v-alert v-if="error || deleteError" type="error" class="mb-4" v-text="error || deleteError" closable />

	<v-row class="mb-n10">
		<v-col cols="12" sm="9">
			<v-sheet rounded="lg">
				<Toolbar color="secondary" :breadcrumb="[...breadcrumb, { title: item?.title ?? '', to: { name: 'events' }}]" :is-loading="isLoading" :text="item?.title" main />

				<v-card-title class="font-title text-center" v-text="item?.title" />

				<v-card-item>
					{{ item?.description }}
				</v-card-item>

				<v-tabs v-model="tab" color="primary" fixed-tabs>
					<v-tab v-for="tab, i in tabs" :="tab" :value="i" />
				</v-tabs>

				<v-window v-if="item?.venue" v-model="tab" class="bg-surface-darken-1">
					<v-window-item value="0">
						<v-row class="bg-surface-darken-1">
							<!-- item.venue.src to the left and details to the right -->
							<v-col cols="12" sm="6">
								<v-img :src="item?.venue.src" :alt="item?.venue.name" />
							</v-col>

							<v-col cols="12" sm="6">
								<v-card-title class="font-title" v-text="item?.venue.name" />
								<v-card-subtitle v-text="item?.venue.location" />

								<v-card-subtitle class="font-subtitle" v-text="item?.venue.description" />
							</v-col>
						</v-row>
					</v-window-item>

					<v-window-item value="1">
						<v-list class="bg-surface-darken-1">
							<v-list-item v-for="artist, i in item?.artists" :key="i" v-text="artist.username" />
						</v-list>
					</v-window-item>

					<v-window-item value="2">
						<v-table class="bg-surface-darken-1">
							<tbody>
								<template v-for="(day, i) in item?.schedules" :key="i">
									<tr>
										<td class="w-0 text-center">
											<div class="mb-n2 text-overline">{{ (new Date(day.date)).toLocaleDateString($vuetify.locale.current, options.weekday) }}</div>
											<div class="text-h6 font-weight-bold">{{ (new Date(day.date)).toLocaleDateString($vuetify.locale.current, options.short) }}</div>
											</td>

											<td>
												<v-list-item
													:value="day.date"
													color="primary"
													rounded="shaped"

												>
													<v-chip-group>
														<v-menu
															v-for="(time, i) in day.times"
															:key="i"
															v-model="menus.times[day.date + 'T' + time]"
															location="top start"
															origin="top start"
															transition="scale-transition"
															:disabled="Date.now() > new Date(day.date + 'T' + time).getTime()"
														>
															<template #activator="{ props }">
																<v-chip v-bind="props" class="text-h6" :text="time" :disabled="Date.now() > new Date(day.date + 'T' + time).getTime()" size="large" link pill />
															</template>

															<v-card width="max-content">
																<v-list bg-color="black">
																	<v-list-item :title="time" :subtitle="(new Date(day.date)).toLocaleDateString($vuetify.locale.current, options.long)" prepend-icon="fa fa-clock">
																		<template #append>
																			<v-list-item-action>
																				<v-btn icon="fa fa-times-circle" variant="text" @click="menus.times[day.date + 'T' + time] = false" />
																			</v-list-item-action>
																		</template>
																	</v-list-item>
																</v-list>

																<v-list>
																	<v-list-item link prepend-icon="fa fa-ticket" title="Buy Tickets" @click="() => $router.push({ name: 'ticketing', query: { event: item?.id, date: day.date, time } })" />
																</v-list>
															</v-card>
														</v-menu>
													</v-chip-group>
												</v-list-item>
											</td>
										</tr>
								</template>
							</tbody>
						</v-table>
					</v-window-item>
				</v-window>
			</v-sheet>
		</v-col>

		<v-col v-if="item" cols="12" sm="3">
			<v-card class="sticky-top sticky-nav overflow-x-hidden overflow-y-auto font-title text-center" rounded="lg" min-height="268" data-simplebar>
				<v-card-title class="my-2" v-text="item?.title" />

				<v-img v-if="typeof item?.src === 'string'" class="card-bg" :src="item.src" cover />

				<v-card-title v-text="'Information'" />

				<v-table class="bg-surface-darken-1" fixed-header>
					<tbody>
						<tr v-for="field, i in general" :key="i">
							<td>{{ t('event.' + field) }}</td>
							<td>{{ field === 'price' ? '$' : '' }}{{ item?.[field] }}</td>
						</tr>
					</tbody>
				</v-table>
			</v-card>
		</v-col>
	</v-row>
</template>