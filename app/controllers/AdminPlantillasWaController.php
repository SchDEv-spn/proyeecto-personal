<?php

class AdminPlantillasWaController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $model     = new PlantillaWa();
        $plantillas = $model->keyedByEstado();
        $saved     = isset($_GET['saved']);

        $this->view('admin/plantillas_wa/index', [
            'plantillas' => $plantillas,
            'saved'      => $saved,
        ]);
    }

    public function guardar()
    {
        $this->requireLogin();
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/AdminPlantillasWa/index');
            exit;
        }

        $estados = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'entregado', 'cancelado'];
        $model   = new PlantillaWa();

        foreach ($estados as $estado) {
            $titulo  = trim($_POST["titulo_{$estado}"]  ?? '');
            $mensaje = trim($_POST["mensaje_{$estado}"] ?? '');
            if ($mensaje !== '') {
                $model->upsert($estado, $titulo, $mensaje);
            }
        }

        header('Location: ' . BASE_URL . '/AdminPlantillasWa/index?saved=1');
        exit;
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/Auth/login');
            exit;
        }
    }
}
