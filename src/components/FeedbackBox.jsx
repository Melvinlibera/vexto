import React, { useState } from 'react';
import { supabase } from '../lib/supabase';
import { Button } from './ui/button';
import { Textarea } from './ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from './ui/dialog';
import { MessageSquare, Send } from 'lucide-react';
import { toast } from 'sonner';

const FeedbackBox = () => {
  const [feedback, setFeedback] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isOpen, setIsOpen] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!feedback.trim()) {
      toast.error('Por favor, ingresa tu comentario.');
      return;
    }

    if (feedback.length > 500) {
      toast.error('El comentario es demasiado largo (máximo 500 caracteres).');
      return;
    }

    setIsSubmitting(true);
    try {
      const { error } = await supabase
        .from('feedback')
        .insert([{ content: feedback.trim(), created_at: new Date() }]);

      if (error) throw error;

      toast.success('¡Gracias por tu feedback!');
      setFeedback('');
      setIsOpen(false);
    } catch (error) {
      console.error('Error sending feedback:', error);
      toast.error('Error al enviar el feedback. Inténtalo de nuevo.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed bottom-4 right-4 z-50">
      <Dialog open={isOpen} onOpenChange={setIsOpen}>
        <DialogTrigger asChild>
          <Button variant="default" size="icon" className="rounded-full h-12 w-12 shadow-lg">
            <MessageSquare className="h-6 w-6" />
          </Button>
        </DialogTrigger>
        <DialogContent className="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Enviar Feedback</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4 pt-4">
            <Textarea
              placeholder="¿Cómo podemos mejorar? Cuéntanos tu experiencia..."
              value={feedback}
              onChange={(e) => setFeedback(e.target.value)}
              className="min-h-[150px] resize-none"
              maxLength={500}
            />
            <div className="flex justify-between items-center text-xs text-muted-foreground">
              <span>{feedback.length}/500 caracteres</span>
            </div>
            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? 'Enviando...' : (
                <>
                  <Send className="mr-2 h-4 w-4" /> Enviar Comentario
                </>
              )}
            </Button>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default FeedbackBox;
