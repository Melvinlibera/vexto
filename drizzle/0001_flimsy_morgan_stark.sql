CREATE TABLE `appointments` (
	`id` int AUTO_INCREMENT NOT NULL,
	`propertyId` int NOT NULL,
	`userId` int NOT NULL,
	`ownerId` int NOT NULL,
	`fechaHora` timestamp,
	`estado` enum('pendiente','confirmada','cancelada','completada') DEFAULT 'pendiente',
	`notas` text,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `appointments_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `favorites` (
	`id` int AUTO_INCREMENT NOT NULL,
	`userId` int NOT NULL,
	`propertyId` int NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `favorites_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `feedback` (
	`id` int AUTO_INCREMENT NOT NULL,
	`userId` int,
	`contenido` text NOT NULL,
	`metadata` json,
	`supabaseId` varchar(255),
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `feedback_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `messages` (
	`id` int AUTO_INCREMENT NOT NULL,
	`senderId` int NOT NULL,
	`receiverId` int NOT NULL,
	`propertyId` int,
	`contenido` text NOT NULL,
	`isRead` boolean DEFAULT false,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `messages_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `properties` (
	`id` int AUTO_INCREMENT NOT NULL,
	`userId` int NOT NULL,
	`titulo` varchar(255) NOT NULL,
	`descripcion` text,
	`tipoPropiedad` enum('casa','apartamento','local','terreno') NOT NULL,
	`tipoOperacion` enum('venta','alquiler') NOT NULL,
	`precio` decimal(15,2) NOT NULL,
	`ubicacion` varchar(255) NOT NULL,
	`latitude` float,
	`longitude` float,
	`habitaciones` int DEFAULT 0,
	`banos` int DEFAULT 0,
	`areaM2` float,
	`imagenUrl` varchar(512),
	`imagen` text,
	`imagenTipo` varchar(50),
	`imagenes` json,
	`views` int DEFAULT 0,
	`favoriteCount` int DEFAULT 0,
	`activa` boolean DEFAULT true,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `properties_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `reports` (
	`id` int AUTO_INCREMENT NOT NULL,
	`propertyId` int NOT NULL,
	`reporterId` int NOT NULL,
	`razon` varchar(255) NOT NULL,
	`descripcion` text,
	`estado` enum('pendiente','revisado','resuelto') DEFAULT 'pendiente',
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `reports_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE TABLE `reviews` (
	`id` int AUTO_INCREMENT NOT NULL,
	`reviewerId` int NOT NULL,
	`revieweeId` int NOT NULL,
	`propertyId` int,
	`rating` int NOT NULL,
	`comentario` text,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `reviews_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
ALTER TABLE `users` ADD `userType` enum('usuario','compania') DEFAULT 'usuario' NOT NULL;--> statement-breakpoint
ALTER TABLE `users` ADD `apellido` varchar(255);--> statement-breakpoint
ALTER TABLE `users` ADD `fotoPerfil` text;--> statement-breakpoint
ALTER TABLE `users` ADD `fotoPerfilTipo` varchar(50);--> statement-breakpoint
ALTER TABLE `users` ADD `bio` text;--> statement-breakpoint
ALTER TABLE `users` ADD `ubicacion` varchar(255);--> statement-breakpoint
ALTER TABLE `users` ADD `rating` float DEFAULT 0;--> statement-breakpoint
ALTER TABLE `users` ADD `totalReviews` int DEFAULT 0;--> statement-breakpoint
ALTER TABLE `users` ADD `verified` boolean DEFAULT false;--> statement-breakpoint
ALTER TABLE `users` ADD `companyName` varchar(255);--> statement-breakpoint
ALTER TABLE `users` ADD `companyWebsite` varchar(255);--> statement-breakpoint
ALTER TABLE `users` ADD `themePreference` enum('light','dark') DEFAULT 'light';--> statement-breakpoint
ALTER TABLE `users` ADD `notificationsEnabled` boolean DEFAULT true;--> statement-breakpoint
ALTER TABLE `users` ADD `publishedCount` int DEFAULT 0;--> statement-breakpoint
ALTER TABLE `users` ADD `maxPublications` int DEFAULT 999;