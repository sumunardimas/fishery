WITH all_reviews AS (
    -- Ballint
    SELECT 'ballint' AS review_type, r.penguji_id, pen.mahasiswa_id
    FROM ballint_reviews r
    JOIN ballints e        ON e.id = r.ballint_id
    JOIN pendaftarans pen  ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Comm Diagnosis Empowerment
    SELECT 'commdiagnosisempowerment', r.penguji_id, pen.mahasiswa_id
    FROM commdiagnosisempowerment_reviews r
    JOIN comm_diagnosis_empowerments e ON e.id = r.commdiagnosisempowerment_id
    JOIN pendaftarans pen              ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Penghargaan
    SELECT 'penghargaan', r.penguji_id, pen.mahasiswa_id
    FROM penghargaan_reviews r
    JOIN penghargaans e    ON e.id = r.penghargaan_id
    JOIN pendaftarans pen  ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Multi Source Feedback
    SELECT 'multisourcefeedback', r.penguji_id, pen.mahasiswa_id
    FROM multisourcefeedback_reviews r
    JOIN multi_source_feedbacks e ON e.id = r.multisourcefeedback_id
    JOIN pendaftarans pen         ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Critical Incidence
    SELECT 'criticalincidence', r.penguji_id, pen.mahasiswa_id
    FROM criticalincidence_reviews r
    JOIN critical_incidences e ON e.id = r.criticalincidence_id
    JOIN pendaftarans pen      ON pen.id = e.pendaftaran_id

    UNION ALL
    -- OSLER
    SELECT 'osler', r.penguji_id, pen.mahasiswa_id
    FROM osler_reviews r
    JOIN oslers e        ON e.id = r.osler_id
    JOIN pendaftarans pen ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Mini-CEX
    SELECT 'minicex', r.penguji_id, pen.mahasiswa_id
    FROM minicex_reviews r
    JOIN mini_cexes e     ON e.id = r.minicex_id
    JOIN pendaftarans pen ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Konsultasi Istimewa
    SELECT 'konsultasiistimewa', r.penguji_id, pen.mahasiswa_id
    FROM konsultasiistimewa_reviews r
    JOIN konsultasi_istimewas e ON e.id = r.konsultasiistimewa_id
    JOIN pendaftarans pen       ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Konseling
    SELECT 'konseling', r.penguji_id, pen.mahasiswa_id
    FROM konseling_reviews r
    JOIN konselings e     ON e.id = r.konseling_id
    JOIN pendaftarans pen ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Perawatan Paliatif
    SELECT 'perawatanpaliatif', r.penguji_id, pen.mahasiswa_id
    FROM perawatanpaliatif_reviews r
    JOIN perawatan_paliatifs e ON e.id = r.perawatanpaliatif_id
    JOIN pendaftarans pen      ON pen.id = e.pendaftaran_id

    UNION ALL
    -- Pertemuan Keluarga
    SELECT 'pertemuankeluarga', r.penguji_id, pen.mahasiswa_id
    FROM pertemuankeluarga_reviews r
    JOIN pertemuan_keluargas e ON e.id = r.pertemuankeluarga_id
    JOIN pendaftarans pen      ON pen.id = e.pendaftaran_id
)
SELECT
    pj.id   AS penguji_id,
    pj.name AS penguji_name,
    mh.id   AS mahasiswa_id,
    mh.nama AS mahasiswa_name,

    LEAST(SUM(CASE WHEN ar.review_type='ballint' THEN 1 ELSE 0 END), 12)                  AS total_review_ballint,
    LEAST(SUM(CASE WHEN ar.review_type='commdiagnosisempowerment' THEN 1 ELSE 0 END), 12) AS total_review_commdiagnosisempowerment,
    LEAST(SUM(CASE WHEN ar.review_type='penghargaan' THEN 1 ELSE 0 END), 12)              AS total_review_penghargaan,
    LEAST(SUM(CASE WHEN ar.review_type='multisourcefeedback' THEN 1 ELSE 0 END), 12)      AS total_review_multisourcefeedback,
    LEAST(SUM(CASE WHEN ar.review_type='criticalincidence' THEN 1 ELSE 0 END), 12)        AS total_review_criticalincidence,
    LEAST(SUM(CASE WHEN ar.review_type='osler' THEN 1 ELSE 0 END), 12)                    AS total_review_osler,
    LEAST(SUM(CASE WHEN ar.review_type='minicex' THEN 1 ELSE 0 END), 12)                  AS total_review_minicex,
    LEAST(SUM(CASE WHEN ar.review_type='konsultasiistimewa' THEN 1 ELSE 0 END), 12)       AS total_review_konsultasiistimewa,
    LEAST(SUM(CASE WHEN ar.review_type='konseling' THEN 1 ELSE 0 END), 12)                AS total_review_konseling,
    LEAST(SUM(CASE WHEN ar.review_type='perawatanpaliatif' THEN 1 ELSE 0 END), 12)        AS total_review_perawatanpaliatif,
    LEAST(SUM(CASE WHEN ar.review_type='pertemuankeluarga' THEN 1 ELSE 0 END), 12)        AS total_review_pertemuankeluarga

FROM all_reviews ar
JOIN pengujis pj   ON pj.id = ar.penguji_id
JOIN mahasiswas mh ON mh.id = ar.mahasiswa_id
GROUP BY pj.id, pj.name, mh.id, mh.nama
ORDER BY pj.name, mh.nama;
