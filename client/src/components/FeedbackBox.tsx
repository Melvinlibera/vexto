import { useState } from "react";
import { trpc } from "@/lib/trpc";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { MessageCircle, Loader2 } from "lucide-react";
import { toast } from "sonner";

export default function FeedbackBox() {
  const [open, setOpen] = useState(false);
  const [feedback, setFeedback] = useState("");
  const submitFeedback = trpc.feedback.submit.useMutation();

  const handleSubmit = async () => {
    if (!feedback.trim()) {
      toast.error("Por favor escribe tu feedback");
      return;
    }

    if (feedback.length > 500) {
      toast.error("El feedback no puede exceder 500 caracteres");
      return;
    }

    submitFeedback.mutate(
      {
        contenido: feedback,
      },
      {
        onSuccess: () => {
          toast.success("¡Gracias por tu feedback!");
          setFeedback("");
          setOpen(false);
        },
        onError: () => {
          toast.error("Error al enviar feedback");
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <button
          className="fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-4 shadow-lg hover:shadow-xl transition z-40 flex items-center justify-center"
          title="Enviar feedback"
        >
          <MessageCircle className="w-6 h-6" />
        </button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Enviar Feedback</DialogTitle>
        </DialogHeader>
        <div className="space-y-4">
          <p className="text-sm text-slate-600 dark:text-slate-400">
            Cuéntanos qué te parece VEXTO. Tu opinión es muy importante para nosotros.
          </p>
          <Textarea
            placeholder="Escribe tu feedback aquí... (máximo 500 caracteres)"
            value={feedback}
            onChange={(e) => setFeedback(e.target.value.slice(0, 500))}
            rows={4}
          />
          <div className="text-xs text-slate-500 dark:text-slate-400">
            {feedback.length}/500
          </div>
          <div className="flex gap-3">
            <Button
              className="flex-1 bg-blue-600 hover:bg-blue-700"
              onClick={handleSubmit}
              disabled={submitFeedback.isPending || !feedback.trim()}
            >
              {submitFeedback.isPending && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
              Enviar
            </Button>
            <Button
              variant="outline"
              className="flex-1"
              onClick={() => setOpen(false)}
            >
              Cancelar
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
