import { COOKIE_NAME } from "@shared/const";
import { getSessionCookieOptions } from "./_core/cookies";
import { systemRouter } from "./_core/systemRouter";
import { publicProcedure, protectedProcedure, router } from "./_core/trpc";
import { z } from "zod";
import * as db from "./db";
import { TRPCError } from "@trpc/server";
import { invokeLLM } from "./_core/llm";
import { notifyOwner } from "./_core/notification";

export const appRouter = router({
  system: systemRouter,
  
  auth: router({
    me: publicProcedure.query(opts => opts.ctx.user),
    logout: publicProcedure.mutation(({ ctx }) => {
      const cookieOptions = getSessionCookieOptions(ctx.req);
      ctx.res.clearCookie(COOKIE_NAME, { ...cookieOptions, maxAge: -1 });
      return {
        success: true,
      } as const;
    }),
  }),

  // User procedures
  user: router({
    getProfile: protectedProcedure
      .input(z.object({ userId: z.number().optional() }))
      .query(async ({ input, ctx }) => {
        const userId = input.userId || ctx.user?.id;
        if (!userId) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.getUserById(userId);
      }),

    updateProfile: protectedProcedure
      .input(z.object({
        name: z.string().optional(),
        apellido: z.string().optional(),
        bio: z.string().optional(),
        ubicacion: z.string().optional(),
        fotoPerfil: z.string().optional(),
        fotoPerfilTipo: z.string().optional(),
        themePreference: z.enum(["light", "dark"]).optional(),
        companyName: z.string().optional(),
        companyWebsite: z.string().optional(),
      }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.updateUser(ctx.user.id, input);
      }),

    getPublications: protectedProcedure
      .query(async ({ ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.getUserProperties(ctx.user.id);
      }),

    getReviews: publicProcedure
      .input(z.object({ userId: z.number() }))
      .query(async ({ input }) => {
        return await db.getUserReviews(input.userId);
      }),

    createReview: protectedProcedure
      .input(z.object({
        revieweeId: z.number(),
        rating: z.number().min(1).max(5),
        comentario: z.string().optional(),
      }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.createReview(ctx.user.id, input.revieweeId, input.rating, input.comentario);
      }),
  }),

  // Property procedures
  property: router({
    list: publicProcedure
      .input(z.object({
        tipoOperacion: z.string().optional(),
        tipoPropiedad: z.string().optional(),
        precioMin: z.number().optional(),
        precioMax: z.number().optional(),
        ubicacion: z.string().optional(),
        search: z.string().optional(),
        limit: z.number().default(20),
        offset: z.number().default(0),
      }))
      .query(async ({ input }) => {
        return await db.getProperties(input);
      }),

    getById: publicProcedure
      .input(z.object({ id: z.number() }))
      .query(async ({ input }) => {
        return await db.getPropertyById(input.id);
      }),

    create: protectedProcedure
      .input(z.object({
        titulo: z.string(),
        descripcion: z.string(),
        tipoPropiedad: z.enum(["casa", "apartamento", "local", "terreno"]),
        tipoOperacion: z.enum(["venta", "alquiler"]),
        precio: z.number(),
        ubicacion: z.string(),
        latitude: z.number().optional(),
        longitude: z.number().optional(),
        habitaciones: z.number().optional(),
        banos: z.number().optional(),
        areaM2: z.number().optional(),
        imagenUrl: z.string().optional(),
        imagenes: z.array(z.string()).optional(),
      }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.createProperty({
          ...input,
          userId: ctx.user.id,
        });
      }),

    update: protectedProcedure
      .input(z.object({
        id: z.number(),
        titulo: z.string().optional(),
        descripcion: z.string().optional(),
        precio: z.number().optional(),
        ubicacion: z.string().optional(),
        latitude: z.number().optional(),
        longitude: z.number().optional(),
        habitaciones: z.number().optional(),
        banos: z.number().optional(),
        areaM2: z.number().optional(),
        imagenUrl: z.string().optional(),
        imagenes: z.array(z.string()).optional(),
      }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        const prop = await db.getPropertyById(input.id);
        if (!prop || prop.userId !== ctx.user.id) {
          throw new TRPCError({ code: "FORBIDDEN" });
        }
        const { id, ...data } = input;
        return await db.updateProperty(id, data);
      }),

    delete: protectedProcedure
      .input(z.object({ id: z.number() }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        const prop = await db.getPropertyById(input.id);
        if (!prop || prop.userId !== ctx.user.id) {
          throw new TRPCError({ code: "FORBIDDEN" });
        }
        return await db.deleteProperty(input.id);
      }),
  }),

  // Favorites procedures
  favorite: router({
    list: protectedProcedure
      .query(async ({ ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.getFavorites(ctx.user.id);
      }),

    add: protectedProcedure
      .input(z.object({ propertyId: z.number() }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.addFavorite(ctx.user.id, input.propertyId);
      }),

    remove: protectedProcedure
      .input(z.object({ propertyId: z.number() }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.removeFavorite(ctx.user.id, input.propertyId);
      }),

    isFavorite: protectedProcedure
      .input(z.object({ propertyId: z.number() }))
      .query(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.isFavorite(ctx.user.id, input.propertyId);
      }),
  }),

  // Message procedures
  message: router({
    getConversations: protectedProcedure
      .query(async ({ ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.getConversations(ctx.user.id);
      }),

    getMessages: protectedProcedure
      .input(z.object({ otherUserId: z.number() }))
      .query(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.getMessages(ctx.user.id, input.otherUserId);
      }),

    send: protectedProcedure
      .input(z.object({
        receiverId: z.number(),
        contenido: z.string(),
        propertyId: z.number().optional(),
      }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.sendMessage(ctx.user.id, input.receiverId, input.contenido, input.propertyId);
      }),

    getUnreadCount: protectedProcedure
      .query(async ({ ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.getUnreadCount(ctx.user.id);
      }),

    markAsRead: protectedProcedure
      .input(z.object({ messageId: z.number() }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.markMessageAsRead(input.messageId);
      }),
  }),

  // Appointment procedures
  appointment: router({
    create: protectedProcedure
      .input(z.object({
        propertyId: z.number(),
        ownerId: z.number(),
        fechaHora: z.date().optional(),
      }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.createAppointment(input.propertyId, ctx.user.id, input.ownerId, input.fechaHora);
      }),

    getPropertyAppointments: protectedProcedure
      .input(z.object({ propertyId: z.number() }))
      .query(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.getPropertyAppointments(input.propertyId);
      }),
  }),

  // Report procedures
  report: router({
    create: protectedProcedure
      .input(z.object({
        propertyId: z.number(),
        razon: z.string(),
        descripcion: z.string().optional(),
      }))
      .mutation(async ({ input, ctx }) => {
        if (!ctx.user?.id) throw new TRPCError({ code: "UNAUTHORIZED" });
        return await db.createReport(input.propertyId, ctx.user.id, input.razon, input.descripcion);
      }),
  }),

  // Feedback procedures
  feedback: router({
    submit: publicProcedure
      .input(z.object({
        contenido: z.string().min(1).max(500),
        userId: z.number().optional(),
      }))
      .mutation(async ({ input, ctx }) => {
        try {
          const feedback = await db.createFeedback(input.userId || null, input.contenido, {
            userAgent: ctx.req.headers["user-agent"],
            timestamp: new Date().toISOString(),
          });

          // Notify owner
          await notifyOwner({
            title: "Nuevo Feedback Recibido",
            content: `Feedback: "${input.contenido.substring(0, 100)}..."`,
          });

          return feedback;
        } catch (error) {
          console.error("Error submitting feedback:", error);
          throw new TRPCError({ code: "INTERNAL_SERVER_ERROR" });
        }
      }),
  }),
});

export type AppRouter = typeof appRouter;
