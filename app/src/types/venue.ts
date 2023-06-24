import type { Item } from './item'
import type { Event } from './event'

export interface Venue extends Item {
	name: string
	type: string
	price: number
	description: string
	seats: number
	location: string
	src?: string
	events: Event[]
}