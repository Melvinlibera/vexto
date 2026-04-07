import { eq, and, or, gte, lte, like, desc, asc } from "drizzle-orm";
import { drizzle } from "drizzle-orm/mysql2";
import {
  InsertUser,
  users,
  properties,
  favorites,
  messages,
  reviews,
  feedback,
  appointments,
  reports,
} from "../drizzle/schema";
import { ENV } from "./_core/env";

let _db: ReturnType<typeof drizzle> | null = null;

export async function getDb() {
  if (!_db && process.env.DATABASE_URL) {
    try {
      _db = drizzle(process.env.DATABASE_URL);
    } catch (error) {
      console.warn("[Database] Failed to connect:", error);
      _db = null;
    }
  }
  return _db;
}

export async function upsertUser(user: InsertUser): Promise<void> {
  if (!user.openId) {
    throw new Error("User openId is required for upsert");
  }

  const db = await getDb();
  if (!db) {
    console.warn("[Database] Cannot upsert user: database not available");
    return;
  }

  try {
    const values: InsertUser = {
      openId: user.openId,
    };
    const updateSet: Record<string, unknown> = {};

    const textFields = ["name", "email", "loginMethod"] as const;
    type TextField = (typeof textFields)[number];

    const assignNullable = (field: TextField) => {
      const value = user[field];
      if (value === undefined) return;
      const normalized = value ?? null;
      values[field] = normalized;
      updateSet[field] = normalized;
    };

    textFields.forEach(assignNullable);

    if (user.lastSignedIn !== undefined) {
      values.lastSignedIn = user.lastSignedIn;
      updateSet.lastSignedIn = user.lastSignedIn;
    }
    if (user.role !== undefined) {
      values.role = user.role;
      updateSet.role = user.role;
    } else if (user.openId === ENV.ownerOpenId) {
      values.role = "admin";
      updateSet.role = "admin";
    }

    if (!values.lastSignedIn) {
      values.lastSignedIn = new Date();
    }

    if (Object.keys(updateSet).length === 0) {
      updateSet.lastSignedIn = new Date();
    }

    await db
      .insert(users)
      .values(values)
      .onDuplicateKeyUpdate({
        set: updateSet,
      });
  } catch (error) {
    console.error("[Database] Failed to upsert user:", error);
    throw error;
  }
}

export async function getUserByOpenId(openId: string) {
  const db = await getDb();
  if (!db) {
    console.warn("[Database] Cannot get user: database not available");
    return undefined;
  }

  const result = await db
    .select()
    .from(users)
    .where(eq(users.openId, openId))
    .limit(1);

  return result.length > 0 ? result[0] : undefined;
}

export async function getUserById(id: number) {
  const db = await getDb();
  if (!db) return undefined;

  const result = await db.select().from(users).where(eq(users.id, id)).limit(1);
  return result.length > 0 ? result[0] : undefined;
}

export async function updateUser(id: number, data: Partial<InsertUser>) {
  const db = await getDb();
  if (!db) return null;

  await db.update(users).set(data).where(eq(users.id, id));
  return getUserById(id);
}

// Properties
export async function getProperties(filters?: {
  tipoOperacion?: string;
  tipoPropiedad?: string;
  precioMin?: number;
  precioMax?: number;
  ubicacion?: string;
  search?: string;
  limit?: number;
  offset?: number;
}) {
  const db = await getDb();
  if (!db) return [];

  const conditions: any[] = [];

  if (filters?.tipoOperacion) {
    conditions.push(eq(properties.tipoOperacion, filters.tipoOperacion as any));
  }
  if (filters?.tipoPropiedad) {
    conditions.push(eq(properties.tipoPropiedad, filters.tipoPropiedad as any));
  }
  if (filters?.precioMin) {
    conditions.push(gte(properties.precio, filters.precioMin.toString()));
  }
  if (filters?.precioMax) {
    conditions.push(lte(properties.precio, filters.precioMax.toString()));
  }
  if (filters?.ubicacion) {
    conditions.push(like(properties.ubicacion, `%${filters.ubicacion}%`));
  }
  if (filters?.search) {
    conditions.push(
      or(
        like(properties.titulo, `%${filters.search}%`),
        like(properties.descripcion, `%${filters.search}%`)
      )
    );
  }

  conditions.push(eq(properties.activa, true));

  let query: any = db.select().from(properties).where(and(...conditions)).orderBy(desc(properties.createdAt));

  if (filters?.limit) {
    query = query.limit(filters.limit);
  }
  if (filters?.offset) {
    query = query.offset(filters.offset);
  }

  return await query;
}

export async function getPropertyById(id: number) {
  const db = await getDb();
  if (!db) return null;

  const result = await db
    .select()
    .from(properties)
    .where(eq(properties.id, id))
    .limit(1);

  if (result.length > 0) {
    // Increment views
    await db
      .update(properties)
      .set({ views: (result[0].views || 0) + 1 })
      .where(eq(properties.id, id));
  }

  return result.length > 0 ? result[0] : null;
}

export async function createProperty(data: any) {
  const db = await getDb();
  if (!db) return null;

  const result = await db.insert(properties).values(data);
  const id = result[0].insertId;

  // Increment user's published count
  await db
    .update(users)
    .set({ publishedCount: (data.userId ? 1 : 0) })
    .where(eq(users.id, data.userId));

  return getPropertyById(Number(id));
}

export async function updateProperty(id: number, data: any) {
  const db = await getDb();
  if (!db) return null;

  await db.update(properties).set(data).where(eq(properties.id, id));
  return getPropertyById(id);
}

export async function deleteProperty(id: number) {
  const db = await getDb();
  if (!db) return false;

  const prop = await getPropertyById(id);
  if (!prop) return false;

  await db.update(properties).set({ activa: false }).where(eq(properties.id, id));

  // Decrement user's published count
  await db
    .update(users)
    .set({ publishedCount: Math.max(0, (prop.userId ? 1 : 0) - 1) })
    .where(eq(users.id, prop.userId));

  return true;
}

export async function getUserProperties(userId: number) {
  const db = await getDb();
  if (!db) return [];

  return await db
    .select()
    .from(properties)
    .where(and(eq(properties.userId, userId), eq(properties.activa, true)))
    .orderBy(desc(properties.createdAt));
}

// Favorites
export async function getFavorites(userId: number) {
  const db = await getDb();
  if (!db) return [];

  const favs = await db
    .select()
    .from(favorites)
    .where(eq(favorites.userId, userId));

  const propIds = favs.map((f) => f.propertyId);
  if (propIds.length === 0) return [];

  return await db
    .select()
    .from(properties)
    .where(and(eq(properties.activa, true)));
}

export async function addFavorite(userId: number, propertyId: number) {
  const db = await getDb();
  if (!db) return false;

  try {
    await db.insert(favorites).values({ userId, propertyId });
    const prop = await getPropertyById(propertyId);
    const newCount = (prop?.favoriteCount || 0) + 1;
    await db
      .update(properties)
      .set({ favoriteCount: newCount })
      .where(eq(properties.id, propertyId));
    return true;
  } catch {
    return false;
  }
}

export async function removeFavorite(userId: number, propertyId: number) {
  const db = await getDb();
  if (!db) return false;

  await db
    .delete(favorites)
    .where(and(eq(favorites.userId, userId), eq(favorites.propertyId, propertyId)));

  const prop = await getPropertyById(propertyId);
  if (prop && prop.favoriteCount) {
    await db
      .update(properties)
      .set({ favoriteCount: Math.max(0, prop.favoriteCount - 1) })
      .where(eq(properties.id, propertyId));
  }

  return true;
}

export async function isFavorite(userId: number, propertyId: number) {
  const db = await getDb();
  if (!db) return false;

  const result = await db
    .select()
    .from(favorites)
    .where(and(eq(favorites.userId, userId), eq(favorites.propertyId, propertyId)))
    .limit(1);

  return result.length > 0;
}

// Messages
export async function getConversations(userId: number) {
  const db = await getDb();
  if (!db) return [];

  const msgs = await db
    .select()
    .from(messages)
    .where(
      or(
        eq(messages.senderId, userId),
        eq(messages.receiverId, userId)
      )
    )
    .orderBy(desc(messages.createdAt));

  // Group by conversation
  const conversations = new Map();
  for (const msg of msgs) {
    const otherId = msg.senderId === userId ? msg.receiverId : msg.senderId;
    if (!conversations.has(otherId)) {
      conversations.set(otherId, msg);
    }
  }

  return Array.from(conversations.values());
}

export async function getMessages(userId: number, otherUserId: number) {
  const db = await getDb();
  if (!db) return [];

  return await db
    .select()
    .from(messages)
    .where(
      or(
        and(eq(messages.senderId, userId), eq(messages.receiverId, otherUserId)),
        and(eq(messages.senderId, otherUserId), eq(messages.receiverId, userId))
      )
    )
    .orderBy(asc(messages.createdAt));
}

export async function sendMessage(senderId: number, receiverId: number, contenido: string, propertyId?: number) {
  const db = await getDb();
  if (!db) return null;

  const result = await db.insert(messages).values({
    senderId,
    receiverId,
    contenido,
    propertyId,
  });

  return {
    id: result[0].insertId,
    senderId,
    receiverId,
    contenido,
    propertyId,
    isRead: false,
    createdAt: new Date(),
  };
}

export async function markMessageAsRead(messageId: number) {
  const db = await getDb();
  if (!db) return false;

  await db.update(messages).set({ isRead: true }).where(eq(messages.id, messageId));
  return true;
}

export async function getUnreadCount(userId: number) {
  const db = await getDb();
  if (!db) return 0;

  const result = await db
    .select()
    .from(messages)
    .where(and(eq(messages.receiverId, userId), eq(messages.isRead, false)));

  return result.length;
}

// Reviews
export async function getUserReviews(userId: number) {
  const db = await getDb();
  if (!db) return [];

  return await db
    .select()
    .from(reviews)
    .where(eq(reviews.revieweeId, userId))
    .orderBy(desc(reviews.createdAt));
}

export async function createReview(reviewerId: number, revieweeId: number, rating: number, comentario?: string) {
  const db = await getDb();
  if (!db) return null;

  const result = await db.insert(reviews).values({
    reviewerId,
    revieweeId,
    rating,
    comentario,
  });

  // Update user rating
  const userReviews = await getUserReviews(revieweeId);
  const avgRating = userReviews.reduce((sum, r) => sum + r.rating, 0) / userReviews.length;

  await db
    .update(users)
    .set({ rating: avgRating, totalReviews: userReviews.length })
    .where(eq(users.id, revieweeId));

  return {
    id: result[0].insertId,
    reviewerId,
    revieweeId,
    rating,
    comentario,
    createdAt: new Date(),
  };
}

// Feedback
export async function createFeedback(userId: number | null, contenido: string, metadata?: any) {
  const db = await getDb();
  if (!db) return null;

  const result = await db.insert(feedback).values({
    userId,
    contenido,
    metadata,
  });

  return {
    id: result[0].insertId,
    userId,
    contenido,
    metadata,
    createdAt: new Date(),
  };
}

// Appointments
export async function createAppointment(propertyId: number, userId: number, ownerId: number, fechaHora?: Date) {
  const db = await getDb();
  if (!db) return null;

  const result = await db.insert(appointments).values({
    propertyId,
    userId,
    ownerId,
    fechaHora,
  });

  return {
    id: result[0].insertId,
    propertyId,
    userId,
    ownerId,
    fechaHora,
    estado: "pendiente",
    createdAt: new Date(),
  };
}

export async function getPropertyAppointments(propertyId: number) {
  const db = await getDb();
  if (!db) return [];

  return await db
    .select()
    .from(appointments)
    .where(eq(appointments.propertyId, propertyId))
    .orderBy(desc(appointments.createdAt));
}

// Reports
export async function createReport(propertyId: number, reporterId: number, razon: string, descripcion?: string) {
  const db = await getDb();
  if (!db) return null;

  const result = await db.insert(reports).values({
    propertyId,
    reporterId,
    razon,
    descripcion,
  });

  return {
    id: result[0].insertId,
    propertyId,
    reporterId,
    razon,
    descripcion,
    estado: "pendiente",
    createdAt: new Date(),
  };
}
