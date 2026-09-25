from flask import Flask, render_template, Response
import cv2
import mediapipe as mp
import sqlite3
from datetime import datetime

app = Flask(__name__)

# Inicializar captura de vídeo e bibliotecas
cap = cv2.VideoCapture(0)
mp_pose = mp.solutions.pose
pose = mp_pose.Pose()
mp_drawing = mp.solutions.drawing_utils
conn = sqlite3.connect('biotipos.db', check_same_thread=False)
cursor = conn.cursor()

# Criar tabela se não existir
cursor.execute('''
    CREATE TABLE IF NOT EXISTS Avaliacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        biotipo TEXT,
        largura_ombros_cm REAL,
        largura_quadris_cm REAL,
        data TEXT
    )
''')

def calcular_proporcoes(landmarks, frame_width):
    # Constante de referência: distância média entre os olhos em pixels
    ref_distancia_olhos = ((landmarks[mp_pose.PoseLandmark.LEFT_EYE.value].x -
                            landmarks[mp_pose.PoseLandmark.RIGHT_EYE.value].x) * frame_width)
    ref_escala_cm = 6.3 / ref_distancia_olhos  # 6.3 cm é a média entre olhos

    # Distâncias em pixels
    ombro_esq = landmarks[mp_pose.PoseLandmark.LEFT_SHOULDER.value]
    ombro_dir = landmarks[mp_pose.PoseLandmark.RIGHT_SHOULDER.value]
    quadril_esq = landmarks[mp_pose.PoseLandmark.LEFT_HIP.value]
    quadril_dir = landmarks[mp_pose.PoseLandmark.RIGHT_HIP.value]

    largura_ombros_px = ((ombro_dir.x - ombro_esq.x) ** 2 + (ombro_dir.y - ombro_esq.y) ** 2) ** 0.5 * frame_width
    largura_quadris_px = ((quadril_dir.x - quadril_esq.x) ** 2 + (quadril_dir.y - quadril_esq.y) ** 2) ** 0.5 * frame_width

    # Converter para centímetros
    largura_ombros_cm = largura_ombros_px * ref_escala_cm
    largura_quadris_cm = largura_quadris_px * ref_escala_cm

    return largura_ombros_cm, largura_quadris_cm

def classificar_biotipo(largura_ombros, largura_quadris):
    # Verificar as condições baseadas na largura dos ombros
    if largura_ombros > 35:
        return "Acima do Peso"
    elif largura_ombros < 32:
        return "Magro"
    
    # Se a largura dos ombros estiver entre 32 e 35 cm, realizar a análise padrão
    relacao_ombro_quadril = largura_ombros / largura_quadris
    if relacao_ombro_quadril > 1.2:
        return "Medio"
    elif relacao_ombro_quadril < 1.0:
        return "Magro"
    else:
        return "Acima do Peso"

def armazenar_avaliacao(biotipo, largura_ombros, largura_quadris):
    data_atual = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    cursor.execute('''
        INSERT INTO Avaliacoes (biotipo, largura_ombros_cm, largura_quadris_cm, data) 
        VALUES (?, ?, ?, ?)
    ''', (biotipo, largura_ombros, largura_quadris, data_atual))
    conn.commit()

def gerar_stream():
    while cap.isOpened():
        ret, frame = cap.read()
        if not ret:
            break
        frame_width = frame.shape[1]
        image_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        result = pose.process(image_rgb)
        if result.pose_landmarks:
            mp_drawing.draw_landmarks(frame, result.pose_landmarks, mp_pose.POSE_CONNECTIONS)
            largura_ombros, largura_quadris = calcular_proporcoes(result.pose_landmarks.landmark, frame_width)
            biotipo = classificar_biotipo(largura_ombros, largura_quadris)
            armazenar_avaliacao(biotipo, largura_ombros, largura_quadris)
            cv2.putText(frame, f"Avalicao fisica: {biotipo}", (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)
            cv2.putText(frame, f"Ombros: {largura_ombros:.1f} cm, Cintura: {largura_quadris:.1f} cm",
                        (10, 70), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 0), 2)
        ret, buffer = cv2.imencode('.jpg', frame)
        frame = buffer.tobytes()
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/video_feed')
def video_feed():
    return Response(gerar_stream(),
                    mimetype='multipart/x-mixed-replace; boundary=frame')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
