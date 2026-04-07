import { useAuth } from "@/_core/hooks/useAuth";
import { useLocation } from "wouter";
import { useEffect, useState } from "react";
import { trpc } from "@/lib/trpc";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card } from "@/components/ui/card";
import { Loader2, Send, MessageCircle } from "lucide-react";
import { toast } from "sonner";

export default function Messages() {
  const { user, isAuthenticated } = useAuth();
  const [, navigate] = useLocation();
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [messageText, setMessageText] = useState("");

  const { data: conversations, isLoading } = trpc.message.getConversations.useQuery();
  const { data: messages } = trpc.message.getMessages.useQuery(
    { otherUserId: selectedUserId || 0 },
    { enabled: !!selectedUserId }
  );
  const { data: unreadCount } = trpc.message.getUnreadCount.useQuery();

  const sendMessage = trpc.message.send.useMutation();

  if (!isAuthenticated) {
    navigate("/");
    return null;
  }

  const handleSendMessage = (e: React.FormEvent) => {
    e.preventDefault();
    if (!messageText.trim() || !selectedUserId) return;

    sendMessage.mutate({
      receiverId: selectedUserId,
      contenido: messageText,
    }, {
      onSuccess: () => {
        setMessageText("");
        toast.success("Mensaje enviado");
      },
      onError: () => {
        toast.error("Error al enviar el mensaje");
      },
    });
  };

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Header */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Mensajes</h1>
          <Button variant="outline" onClick={() => navigate("/dashboard")}>
            Volver
          </Button>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 py-8">
        <div className="grid lg:grid-cols-3 gap-6 h-[600px]">
          {/* Conversations List */}
          <div className="lg:col-span-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col">
            <div className="p-4 border-b border-slate-200 dark:border-slate-800">
              <h2 className="font-semibold text-slate-900 dark:text-white">
                Conversaciones {unreadCount ? `(${unreadCount})` : ""}
              </h2>
            </div>

            {isLoading ? (
              <div className="flex items-center justify-center flex-1">
                <Loader2 className="w-6 h-6 animate-spin text-blue-600" />
              </div>
            ) : conversations && conversations.length > 0 ? (
              <div className="overflow-y-auto flex-1">
                {conversations.map((conv: any) => (
                  <button
                    key={conv.id}
                    onClick={() => setSelectedUserId(conv.senderId === user?.id ? conv.receiverId : conv.senderId)}
                    className={`w-full p-4 border-b border-slate-200 dark:border-slate-800 text-left hover:bg-slate-50 dark:hover:bg-slate-800 transition ${
                      selectedUserId === (conv.senderId === user?.id ? conv.receiverId : conv.senderId)
                        ? "bg-blue-50 dark:bg-blue-900"
                        : ""
                    }`}
                  >
                    <p className="font-semibold text-slate-900 dark:text-white truncate">
                      Usuario {conv.senderId === user?.id ? conv.receiverId : conv.senderId}
                    </p>
                    <p className="text-sm text-slate-600 dark:text-slate-400 truncate">
                      {conv.contenido}
                    </p>
                  </button>
                ))}
              </div>
            ) : (
              <div className="flex items-center justify-center flex-1 text-slate-600 dark:text-slate-400">
                <div className="text-center">
                  <MessageCircle className="w-8 h-8 mx-auto mb-2 opacity-50" />
                  <p>No hay conversaciones</p>
                </div>
              </div>
            )}
          </div>

          {/* Chat Area */}
          <div className="lg:col-span-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col">
            {selectedUserId ? (
              <>
                {/* Messages */}
                <div className="flex-1 overflow-y-auto p-4 space-y-4">
                  {messages && messages.length > 0 ? (
                    messages.map((msg: any) => (
                      <div
                        key={msg.id}
                        className={`flex ${msg.senderId === user?.id ? "justify-end" : "justify-start"}`}
                      >
                        <div
                          className={`max-w-xs px-4 py-2 rounded-lg ${
                            msg.senderId === user?.id
                              ? "bg-blue-600 text-white"
                              : "bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white"
                          }`}
                        >
                          <p>{msg.contenido}</p>
                          <p className={`text-xs mt-1 ${msg.senderId === user?.id ? "text-blue-100" : "text-slate-500 dark:text-slate-400"}`}>
                            {new Date(msg.createdAt).toLocaleTimeString()}
                          </p>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="flex items-center justify-center h-full text-slate-600 dark:text-slate-400">
                      <p>No hay mensajes</p>
                    </div>
                  )}
                </div>

                {/* Input */}
                <form onSubmit={handleSendMessage} className="p-4 border-t border-slate-200 dark:border-slate-800 flex gap-2">
                  <Input
                    placeholder="Escribe tu mensaje..."
                    value={messageText}
                    onChange={(e) => setMessageText(e.target.value)}
                  />
                  <Button
                    type="submit"
                    className="bg-blue-600 hover:bg-blue-700"
                    disabled={sendMessage.isPending || !messageText.trim()}
                  >
                    {sendMessage.isPending ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <Send className="w-4 h-4" />
                    )}
                  </Button>
                </form>
              </>
            ) : (
              <div className="flex items-center justify-center h-full text-slate-600 dark:text-slate-400">
                <div className="text-center">
                  <MessageCircle className="w-12 h-12 mx-auto mb-4 opacity-50" />
                  <p>Selecciona una conversación para empezar</p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
