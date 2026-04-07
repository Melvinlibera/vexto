import {
  int,
  mysqlEnum,
  mysqlTable,
  text,
  timestamp,
  varchar,
  decimal,
  boolean,
  float,
  json,
} from "drizzle-orm/mysql-core";

/**
 * Core user table backing auth flow.
 * Extended with real estate platform fields.
 */
export const users = mysqlTable("users", {
  id: int("id").autoincrement().primaryKey(),
  openId: varchar("openId", { length: 64 }).notNull().unique(),
  name: text("name"),
  email: varchar("email", { length: 320 }),
  loginMethod: varchar("loginMethod", { length: 64 }),
  role: mysqlEnum("role", ["user", "admin"]).default("user").notNull(),
  
  // Real estate specific fields
  userType: mysqlEnum("userType", ["usuario", "compania"]).default("usuario").notNull(),
  apellido: varchar("apellido", { length: 255 }),
  fotoPerfil: text("fotoPerfil"), // S3 URL
  fotoPerfilTipo: varchar("fotoPerfilTipo", { length: 50 }), // MIME type
  bio: text("bio"),
  ubicacion: varchar("ubicacion", { length: 255 }),
  
  // Rating and verification
  rating: float("rating").default(0),
  totalReviews: int("totalReviews").default(0),
  verified: boolean("verified").default(false),
  
  // Company specific
  companyName: varchar("companyName", { length: 255 }),
  companyWebsite: varchar("companyWebsite", { length: 255 }),
  
  // Preferences
  themePreference: mysqlEnum("themePreference", ["light", "dark"]).default("light"),
  notificationsEnabled: boolean("notificationsEnabled").default(true),
  
  // Publishing limits
  publishedCount: int("publishedCount").default(0),
  maxPublications: int("maxPublications").default(999),
  
  createdAt: timestamp("createdAt").defaultNow().notNull(),
  updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
  lastSignedIn: timestamp("lastSignedIn").defaultNow().notNull(),
});

export type User = typeof users.$inferSelect;
export type InsertUser = typeof users.$inferInsert;

/**
 * Properties table for real estate listings
 */
export const properties = mysqlTable("properties", {
  id: int("id").autoincrement().primaryKey(),
  userId: int("userId").notNull(),
  
  // Basic info
  titulo: varchar("titulo", { length: 255 }).notNull(),
  descripcion: text("descripcion"),
  
  // Property details
  tipoPropiedad: mysqlEnum("tipoPropiedad", ["casa", "apartamento", "local", "terreno"]).notNull(),
  tipoOperacion: mysqlEnum("tipoOperacion", ["venta", "alquiler"]).notNull(),
  
  // Pricing
  precio: decimal("precio", { precision: 15, scale: 2 }).notNull(),
  
  // Location
  ubicacion: varchar("ubicacion", { length: 255 }).notNull(),
  latitude: float("latitude"),
  longitude: float("longitude"),
  
  // Property specs
  habitaciones: int("habitaciones").default(0),
  banos: int("banos").default(0),
  areaM2: float("areaM2"),
  
  // Images
  imagenUrl: varchar("imagenUrl", { length: 512 }), // S3 URL
  imagen: text("imagen"), // Base64 backup (deprecated, use S3)
  imagenTipo: varchar("imagenTipo", { length: 50 }),
  imagenes: json("imagenes"), // Array of S3 URLs
  
  // Stats
  views: int("views").default(0),
  favoriteCount: int("favoriteCount").default(0),
  
  // Status
  activa: boolean("activa").default(true),
  
  createdAt: timestamp("createdAt").defaultNow().notNull(),
  updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
});

export type Property = typeof properties.$inferSelect;
export type InsertProperty = typeof properties.$inferInsert;

/**
 * Favorites table for bookmarked properties
 */
export const favorites = mysqlTable("favorites", {
  id: int("id").autoincrement().primaryKey(),
  userId: int("userId").notNull(),
  propertyId: int("propertyId").notNull(),
  createdAt: timestamp("createdAt").defaultNow().notNull(),
});

export type Favorite = typeof favorites.$inferSelect;
export type InsertFavorite = typeof favorites.$inferInsert;

/**
 * Messages table for user-to-user communication
 */
export const messages = mysqlTable("messages", {
  id: int("id").autoincrement().primaryKey(),
  senderId: int("senderId").notNull(),
  receiverId: int("receiverId").notNull(),
  propertyId: int("propertyId"),
  
  contenido: text("contenido").notNull(),
  isRead: boolean("isRead").default(false),
  
  createdAt: timestamp("createdAt").defaultNow().notNull(),
});

export type Message = typeof messages.$inferSelect;
export type InsertMessage = typeof messages.$inferInsert;

/**
 * Reviews table for user ratings and comments
 */
export const reviews = mysqlTable("reviews", {
  id: int("id").autoincrement().primaryKey(),
  reviewerId: int("reviewerId").notNull(), // User who wrote the review
  revieweeId: int("revieweeId").notNull(), // User being reviewed
  propertyId: int("propertyId"),
  
  rating: int("rating").notNull(), // 1-5 stars
  comentario: text("comentario"),
  
  createdAt: timestamp("createdAt").defaultNow().notNull(),
  updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
});

export type Review = typeof reviews.$inferSelect;
export type InsertReview = typeof reviews.$inferInsert;

/**
 * Feedback table for user feedback (stored in Supabase via API)
 * This is a local reference table
 */
export const feedback = mysqlTable("feedback", {
  id: int("id").autoincrement().primaryKey(),
  userId: int("userId"),
  contenido: text("contenido").notNull(),
  metadata: json("metadata"), // User agent, page, etc.
  supabaseId: varchar("supabaseId", { length: 255 }), // Reference to Supabase row
  createdAt: timestamp("createdAt").defaultNow().notNull(),
});

export type Feedback = typeof feedback.$inferSelect;
export type InsertFeedback = typeof feedback.$inferInsert;

/**
 * Appointments table for property viewings
 */
export const appointments = mysqlTable("appointments", {
  id: int("id").autoincrement().primaryKey(),
  propertyId: int("propertyId").notNull(),
  userId: int("userId").notNull(),
  ownerId: int("ownerId").notNull(),
  
  fechaHora: timestamp("fechaHora"),
  estado: mysqlEnum("estado", ["pendiente", "confirmada", "cancelada", "completada"]).default("pendiente"),
  notas: text("notas"),
  
  createdAt: timestamp("createdAt").defaultNow().notNull(),
  updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
});

export type Appointment = typeof appointments.$inferSelect;
export type InsertAppointment = typeof appointments.$inferInsert;

/**
 * Reports table for flagging inappropriate listings
 */
export const reports = mysqlTable("reports", {
  id: int("id").autoincrement().primaryKey(),
  propertyId: int("propertyId").notNull(),
  reporterId: int("reporterId").notNull(),
  
  razon: varchar("razon", { length: 255 }).notNull(),
  descripcion: text("descripcion"),
  estado: mysqlEnum("estado", ["pendiente", "revisado", "resuelto"]).default("pendiente"),
  
  createdAt: timestamp("createdAt").defaultNow().notNull(),
  updatedAt: timestamp("updatedAt").defaultNow().onUpdateNow().notNull(),
});

export type Report = typeof reports.$inferSelect;
export type InsertReport = typeof reports.$inferInsert;
