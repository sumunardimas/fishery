-- fishery.app_settings definition

CREATE TABLE `app_settings` (
  `key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.arus_kas definition

CREATE TABLE `arus_kas` (
  `id_kas` int unsigned NOT NULL AUTO_INCREMENT,
  `akun` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kas',
  `tanggal` date NOT NULL,
  `jenis_transaksi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `uang_masuk` decimal(15,2) NOT NULL,
  `uang_keluar` decimal(15,2) NOT NULL,
  `saldo` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_kas`),
  KEY `arus_kas_akun_tanggal_index` (`akun`,`tanggal`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.cache definition

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.cache_locks definition

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.failed_jobs definition

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.job_batches definition

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.jobs definition

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.kapal definition

CREATE TABLE `kapal` (
  `id_kapal` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_kapal` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nahkoda` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_kapal`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.kas_harian definition

CREATE TABLE `kas_harian` (
  `id_kas_harian` int unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `saldo_awal` decimal(15,2) NOT NULL,
  `total_masuk` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_keluar` decimal(15,2) NOT NULL DEFAULT '0.00',
  `saldo_akhir` decimal(15,2) NOT NULL,
  `status_tutup` tinyint(1) NOT NULL DEFAULT '0',
  `waktu_tutup` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_kas_harian`),
  UNIQUE KEY `kas_harian_tanggal_unique` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.laba definition

CREATE TABLE `laba` (
  `id_laba` int unsigned NOT NULL AUTO_INCREMENT,
  `periode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_pendapatan` decimal(15,2) NOT NULL,
  `total_pengeluaran` decimal(15,2) NOT NULL,
  `laba_bersih` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_laba`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.laporan_penjualan definition

CREATE TABLE `laporan_penjualan` (
  `id_laporan` int unsigned NOT NULL AUTO_INCREMENT,
  `periode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_penjualan` decimal(15,2) NOT NULL,
  `total_berat` decimal(15,2) NOT NULL,
  `total_pendapatan` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_laporan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.lelang_ikan_harian definition

CREATE TABLE `lelang_ikan_harian` (
  `id_lelang_harian` int unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `berat_lelang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_lelang_harian`),
  UNIQUE KEY `lelang_ikan_harian_tanggal_unique` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.master_customer definition

CREATE TABLE `master_customer` (
  `id_customer` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_customer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telepon` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_customer`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.master_gudang definition

CREATE TABLE `master_gudang` (
  `id_gudang` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_gudang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `penanggung_jawab` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_gudang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.master_ikan definition

CREATE TABLE `master_ikan` (
  `id_ikan` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_ikan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_ikan_tangkapan` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_ikan`),
  KEY `master_ikan_id_ikan_tangkapan_index` (`id_ikan_tangkapan`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.master_ikan_tangkapan definition

CREATE TABLE `master_ikan_tangkapan` (
  `id_ikan_tangkapan` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_ikan_tangkapan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_ikan_tangkapan`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.master_item_pembelian definition

CREATE TABLE `master_item_pembelian` (
  `id_item_pembelian` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_item` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `satuan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_item_pembelian`),
  UNIQUE KEY `master_item_pembelian_nama_item_unique` (`nama_item`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.master_operasional definition

CREATE TABLE `master_operasional` (
  `id_master_operasional` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_operasional` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_master_operasional`),
  UNIQUE KEY `master_operasional_nama_operasional_unique` (`nama_operasional`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.master_operasional_kantor definition

CREATE TABLE `master_operasional_kantor` (
  `id_master_operasional_kantor` int unsigned NOT NULL AUTO_INCREMENT,
  `item` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` enum('Operasional','Gaji','Retribusi','Transportasi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_master_operasional_kantor`),
  UNIQUE KEY `master_operasional_kantor_item_unique` (`item`),
  KEY `master_operasional_kantor_kategori_index` (`kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.master_perbekalan definition

CREATE TABLE `master_perbekalan` (
  `id_barang` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `satuan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `default` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_barang`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.migrations definition

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.password_reset_tokens definition

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.permissions definition

CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.roles definition

CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.sessions definition

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.users definition

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.admins definition

CREATE TABLE `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admins_user_id_foreign` (`user_id`),
  CONSTRAINT `admins_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.item_pembelian_stock definition

CREATE TABLE `item_pembelian_stock` (
  `id_stok_item_pembelian` int unsigned NOT NULL AUTO_INCREMENT,
  `id_item_pembelian` int unsigned NOT NULL,
  `stok_aktual` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_stok_item_pembelian`),
  UNIQUE KEY `uniq_item_pembelian_stock` (`id_item_pembelian`),
  CONSTRAINT `gudang_item_pembelian_stock_id_item_pembelian_foreign` FOREIGN KEY (`id_item_pembelian`) REFERENCES `master_item_pembelian` (`id_item_pembelian`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.jons_group_debts definition

CREATE TABLE `jons_group_debts` (
  `id_jons_group_debt` int unsigned NOT NULL AUTO_INCREMENT,
  `id_kas_sumber` int unsigned NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `akun_penerimaan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `nominal_awal` decimal(15,2) NOT NULL,
  `sisa_hutang` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_jons_group_debt`),
  UNIQUE KEY `jons_group_debts_id_kas_sumber_unique` (`id_kas_sumber`),
  KEY `jons_group_debts_tanggal_pinjam_index` (`tanggal_pinjam`),
  KEY `jons_group_debts_akun_penerimaan_index` (`akun_penerimaan`),
  CONSTRAINT `jons_group_debts_id_kas_sumber_foreign` FOREIGN KEY (`id_kas_sumber`) REFERENCES `arus_kas` (`id_kas`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.kas_bon_pegawai definition

CREATE TABLE `kas_bon_pegawai` (
  `id_kas_bon_pegawai` int unsigned NOT NULL AUTO_INCREMENT,
  `id_kas_sumber` int unsigned NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `akun_pengeluaran` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pegawai` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal_awal` decimal(15,2) NOT NULL,
  `sisa_piutang` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_kas_bon_pegawai`),
  UNIQUE KEY `kas_bon_pegawai_id_kas_sumber_unique` (`id_kas_sumber`),
  KEY `kas_bon_pegawai_tanggal_pinjam_index` (`tanggal_pinjam`),
  KEY `kas_bon_pegawai_nama_pegawai_index` (`nama_pegawai`),
  KEY `kas_bon_pegawai_akun_pengeluaran_index` (`akun_pengeluaran`),
  CONSTRAINT `kas_bon_pegawai_id_kas_sumber_foreign` FOREIGN KEY (`id_kas_sumber`) REFERENCES `arus_kas` (`id_kas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.kas_induk_transfers definition

CREATE TABLE `kas_induk_transfers` (
  `id_kas_induk_transfer` int unsigned NOT NULL AUTO_INCREMENT,
  `id_kas_sumber` int unsigned NOT NULL,
  `tanggal_setor` date NOT NULL,
  `akun_sumber` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `nominal` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_kas_induk_transfer`),
  UNIQUE KEY `kas_induk_transfers_id_kas_sumber_unique` (`id_kas_sumber`),
  KEY `kas_induk_transfers_tanggal_setor_index` (`tanggal_setor`),
  KEY `kas_induk_transfers_akun_sumber_index` (`akun_sumber`),
  CONSTRAINT `kas_induk_transfers_id_kas_sumber_foreign` FOREIGN KEY (`id_kas_sumber`) REFERENCES `arus_kas` (`id_kas`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.kasirs definition

CREATE TABLE `kasirs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` smallint NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kasirs_user_id_foreign` (`user_id`),
  CONSTRAINT `kasirs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.model_has_permissions definition

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.model_has_roles definition

CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.operasional_kantor definition

CREATE TABLE `operasional_kantor` (
  `id_operasional_kantor` int unsigned NOT NULL AUTO_INCREMENT,
  `id_master_operasional_kantor` int unsigned DEFAULT NULL,
  `jenis_biaya` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_satuan` decimal(15,2) DEFAULT NULL,
  `qty` decimal(15,2) DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `total_biaya` decimal(15,2) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `akun_pembayaran` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominal_terbayar_hutang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_operasional_kantor`),
  KEY `fk_operasional_kantor_master` (`id_master_operasional_kantor`),
  KEY `idx_operasional_kantor_tanggal_kategori` (`tanggal`,`kategori`),
  KEY `idx_operasional_kantor_akun_pembayaran` (`akun_pembayaran`),
  CONSTRAINT `fk_operasional_kantor_master` FOREIGN KEY (`id_master_operasional_kantor`) REFERENCES `master_operasional_kantor` (`id_master_operasional_kantor`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.pelayaran definition

CREATE TABLE `pelayaran` (
  `id_pelayaran` int unsigned NOT NULL AUTO_INCREMENT,
  `id_kapal` int unsigned NOT NULL,
  `tanggal_berangkat` date NOT NULL,
  `tanggal_tiba` date NOT NULL,
  `jumlah_trip` int NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_pelayaran` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `tanggal_selesai` date DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_pelayaran`),
  KEY `pelayaran_id_kapal_foreign` (`id_kapal`),
  CONSTRAINT `pelayaran_id_kapal_foreign` FOREIGN KEY (`id_kapal`) REFERENCES `kapal` (`id_kapal`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.pemakaian_barang_kantor definition

CREATE TABLE `pemakaian_barang_kantor` (
  `id_pemakaian` int unsigned NOT NULL AUTO_INCREMENT,
  `id_barang` int unsigned NOT NULL,
  `id_gudang` int unsigned DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `satuan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_pemakaian`),
  KEY `pemakaian_barang_kantor_id_barang_foreign` (`id_barang`),
  KEY `pemakaian_barang_kantor_id_gudang_foreign` (`id_gudang`),
  CONSTRAINT `pemakaian_barang_kantor_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `master_perbekalan` (`id_barang`),
  CONSTRAINT `pemakaian_barang_kantor_id_gudang_foreign` FOREIGN KEY (`id_gudang`) REFERENCES `master_gudang` (`id_gudang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.pembelian_barang definition

CREATE TABLE `pembelian_barang` (
  `id_pembelian` int unsigned NOT NULL AUTO_INCREMENT,
  `tanggal_pembelian` date NOT NULL,
  `id_barang` int unsigned NOT NULL,
  `id_gudang` int unsigned DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `satuan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `total_harga` decimal(15,2) NOT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_pembelian`),
  KEY `pembelian_barang_id_barang_foreign` (`id_barang`),
  KEY `pembelian_barang_id_gudang_foreign` (`id_gudang`),
  CONSTRAINT `pembelian_barang_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `master_perbekalan` (`id_barang`),
  CONSTRAINT `pembelian_barang_id_gudang_foreign` FOREIGN KEY (`id_gudang`) REFERENCES `master_gudang` (`id_gudang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.pembelian_transaction definition

CREATE TABLE `pembelian_transaction` (
  `id_transaction` int unsigned NOT NULL AUTO_INCREMENT,
  `tanggal_transaksi` date NOT NULL,
  `id_item_pembelian` int unsigned NOT NULL,
  `jenis_transaksi` enum('in','out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `akun_pembayaran` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `harga_satuan` decimal(15,2) DEFAULT NULL,
  `total_harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `nominal_terbayar_hutang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sumber_tujuan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_transaction`),
  KEY `pembelian_transaction_id_item_pembelian_tanggal_transaksi_index` (`id_item_pembelian`,`tanggal_transaksi`),
  KEY `pembelian_transaction_akun_pembayaran_index` (`akun_pembayaran`),
  CONSTRAINT `pembelian_transaction_id_item_pembelian_foreign` FOREIGN KEY (`id_item_pembelian`) REFERENCES `master_item_pembelian` (`id_item_pembelian`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.pendapatan definition

CREATE TABLE `pendapatan` (
  `id_pendapatan` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pelayaran` int unsigned NOT NULL,
  `sumber_pendapatan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_pendapatan`),
  KEY `pendapatan_id_pelayaran_foreign` (`id_pelayaran`),
  CONSTRAINT `pendapatan_id_pelayaran_foreign` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penjualan definition

CREATE TABLE `penjualan` (
  `id_penjualan` int unsigned NOT NULL AUTO_INCREMENT,
  `tanggal_penjualan` date NOT NULL,
  `id_ikan` int unsigned DEFAULT NULL,
  `id_customer` int unsigned DEFAULT NULL,
  `berat` decimal(15,2) DEFAULT NULL,
  `harga_per_kg` decimal(15,2) DEFAULT NULL,
  `total_harga` decimal(15,2) NOT NULL,
  `bayar_tunai` decimal(15,2) NOT NULL DEFAULT '0.00',
  `bayar_transfer` decimal(15,2) NOT NULL DEFAULT '0.00',
  `piutang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status_pembayaran` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lunas',
  `pembeli` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_penjualan`),
  KEY `penjualan_id_ikan_foreign` (`id_ikan`),
  KEY `penjualan_id_customer_foreign` (`id_customer`),
  CONSTRAINT `penjualan_id_customer_foreign` FOREIGN KEY (`id_customer`) REFERENCES `master_customer` (`id_customer`) ON DELETE SET NULL,
  CONSTRAINT `penjualan_id_ikan_foreign` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penjualan_cart_drafts definition

CREATE TABLE `penjualan_cart_drafts` (
  `id_penjualan_cart_draft` int unsigned NOT NULL AUTO_INCREMENT,
  `id_user` bigint unsigned NOT NULL,
  `id_customer` int unsigned NOT NULL,
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_penjualan_cart_draft`),
  UNIQUE KEY `uq_penjualan_cart_draft_user_customer` (`id_user`,`id_customer`),
  KEY `penjualan_cart_drafts_id_customer_foreign` (`id_customer`),
  KEY `penjualan_cart_drafts_updated_at_index` (`updated_at`),
  CONSTRAINT `penjualan_cart_drafts_id_customer_foreign` FOREIGN KEY (`id_customer`) REFERENCES `master_customer` (`id_customer`) ON DELETE CASCADE,
  CONSTRAINT `penjualan_cart_drafts_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penjualan_items definition

CREATE TABLE `penjualan_items` (
  `id_item` int unsigned NOT NULL AUTO_INCREMENT,
  `id_penjualan` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `berat` decimal(15,2) NOT NULL,
  `harga_per_kg` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_item`),
  KEY `penjualan_items_id_penjualan_foreign` (`id_penjualan`),
  KEY `penjualan_items_id_ikan_foreign` (`id_ikan`),
  CONSTRAINT `penjualan_items_id_ikan_foreign` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`),
  CONSTRAINT `penjualan_items_id_penjualan_foreign` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penjualan_selisih_stok definition

CREATE TABLE `penjualan_selisih_stok` (
  `id_penjualan_selisih` int unsigned NOT NULL AUTO_INCREMENT,
  `id_penjualan` int unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `catatan_kasir` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan_admin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_penjualan_selisih`),
  UNIQUE KEY `penjualan_selisih_stok_id_penjualan_unique` (`id_penjualan`),
  CONSTRAINT `penjualan_selisih_stok_id_penjualan_foreign` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penjualan_selisih_stok_items definition

CREATE TABLE `penjualan_selisih_stok_items` (
  `id_penjualan_selisih_item` int unsigned NOT NULL AUTO_INCREMENT,
  `id_penjualan_selisih` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `stok_tersedia` decimal(15,2) NOT NULL DEFAULT '0.00',
  `berat_diminta` decimal(15,2) NOT NULL DEFAULT '0.00',
  `berat_selisih` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_penjualan_selisih_item`),
  KEY `fk_penjualan_selisih_items_header` (`id_penjualan_selisih`),
  KEY `penjualan_selisih_stok_items_id_ikan_foreign` (`id_ikan`),
  CONSTRAINT `fk_penjualan_selisih_items_header` FOREIGN KEY (`id_penjualan_selisih`) REFERENCES `penjualan_selisih_stok` (`id_penjualan_selisih`) ON DELETE CASCADE,
  CONSTRAINT `penjualan_selisih_stok_items_id_ikan_foreign` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penyesuaian_stok_ikan definition

CREATE TABLE `penyesuaian_stok_ikan` (
  `id_penyesuaian_stok` int unsigned NOT NULL AUTO_INCREMENT,
  `id_penjualan_selisih` int unsigned DEFAULT NULL,
  `tipe_sumber` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_penyesuaian_stok`),
  KEY `fk_penyesuaian_stok_discrepancy` (`id_penjualan_selisih`),
  CONSTRAINT `fk_penyesuaian_stok_discrepancy` FOREIGN KEY (`id_penjualan_selisih`) REFERENCES `penjualan_selisih_stok` (`id_penjualan_selisih`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.perbekalan definition

CREATE TABLE `perbekalan` (
  `id_perbekalan` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pelayaran` int unsigned NOT NULL,
  `id_barang` int unsigned NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `satuan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `total_harga` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_perbekalan`),
  KEY `perbekalan_id_pelayaran_foreign` (`id_pelayaran`),
  KEY `perbekalan_id_barang_foreign` (`id_barang`),
  CONSTRAINT `perbekalan_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `master_perbekalan` (`id_barang`),
  CONSTRAINT `perbekalan_id_pelayaran_foreign` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.perbekalan_pelayaran definition

CREATE TABLE `perbekalan_pelayaran` (
  `id_perbekalan_pelayaran` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pelayaran` int unsigned NOT NULL,
  `id_barang` int unsigned NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_perbekalan_pelayaran`),
  UNIQUE KEY `perbekalan_pelayaran_id_pelayaran_id_barang_unique` (`id_pelayaran`,`id_barang`),
  KEY `perbekalan_pelayaran_id_barang_foreign` (`id_barang`),
  CONSTRAINT `perbekalan_pelayaran_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `master_perbekalan` (`id_barang`),
  CONSTRAINT `perbekalan_pelayaran_id_pelayaran_foreign` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.perbekalan_stock definition

CREATE TABLE `perbekalan_stock` (
  `id_stok_perbekalan` int unsigned NOT NULL AUTO_INCREMENT,
  `id_barang` int unsigned NOT NULL,
  `stok_aktual` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_stok_perbekalan`),
  UNIQUE KEY `uniq_perbekalan_stock_barang` (`id_barang`),
  CONSTRAINT `perbekalan_stock_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `master_perbekalan` (`id_barang`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.perbekalan_transaction definition

CREATE TABLE `perbekalan_transaction` (
  `id_transaction` int unsigned NOT NULL AUTO_INCREMENT,
  `tanggal_transaksi` date NOT NULL,
  `id_barang` int unsigned NOT NULL,
  `id_pelayaran` int unsigned DEFAULT NULL,
  `jenis_transaksi` enum('in','out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `akun_pembayaran` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `harga_satuan` decimal(15,2) DEFAULT NULL,
  `total_harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `nominal_terbayar_hutang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sumber_tujuan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_transaction`),
  KEY `idx_perbekalan_trx_barang_tanggal` (`id_barang`,`tanggal_transaksi`),
  KEY `idx_perbekalan_trx_akun_pembayaran` (`akun_pembayaran`),
  KEY `idx_perbekalan_trx_pelayaran_barang` (`id_pelayaran`,`id_barang`),
  CONSTRAINT `fk_perbekalan_trx_pelayaran` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`) ON DELETE SET NULL,
  CONSTRAINT `perbekalan_transaction_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `master_perbekalan` (`id_barang`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.role_has_permissions definition

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.sisa_trip definition

CREATE TABLE `sisa_trip` (
  `id_sisa_trip` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pelayaran` int unsigned NOT NULL,
  `id_barang` int unsigned NOT NULL,
  `jumlah_sisa` decimal(15,2) NOT NULL,
  `satuan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_sisa_trip`),
  KEY `sisa_trip_id_pelayaran_foreign` (`id_pelayaran`),
  KEY `sisa_trip_id_barang_foreign` (`id_barang`),
  CONSTRAINT `sisa_trip_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `master_perbekalan` (`id_barang`),
  CONSTRAINT `sisa_trip_id_pelayaran_foreign` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.staffs definition

CREATE TABLE `staffs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` smallint DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staffs_user_id_foreign` (`user_id`),
  CONSTRAINT `staffs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.stok_ikan definition

CREATE TABLE `stok_ikan` (
  `id_stok` int unsigned NOT NULL AUTO_INCREMENT,
  `id_ikan` int unsigned NOT NULL,
  `periode` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_tangkapan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_penjualan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stok_akhir` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_stok`),
  UNIQUE KEY `stok_ikan_id_ikan_periode_unique` (`id_ikan`,`periode`),
  CONSTRAINT `stok_ikan_id_ikan_foreign` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.storage_ikan definition

CREATE TABLE `storage_ikan` (
  `id_storage` int unsigned NOT NULL AUTO_INCREMENT,
  `id_kapal` int unsigned NOT NULL,
  `nama_storage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_storage`),
  UNIQUE KEY `storage_ikan_id_kapal_unique` (`id_kapal`),
  CONSTRAINT `storage_ikan_id_kapal_foreign` FOREIGN KEY (`id_kapal`) REFERENCES `kapal` (`id_kapal`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.bongkaran definition

CREATE TABLE `bongkaran` (
  `id_bongkaran` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pelayaran` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `berat_timbangan` decimal(15,2) NOT NULL,
  `berat_tercatat` decimal(15,2) NOT NULL,
  `selisih_berat` decimal(15,2) NOT NULL,
  `harga_per_kg` decimal(15,2) NOT NULL,
  `total_nilai` decimal(15,2) NOT NULL,
  `tanggal_bongkar` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_bongkaran`),
  KEY `bongkaran_id_pelayaran_foreign` (`id_pelayaran`),
  KEY `bongkaran_id_ikan_foreign` (`id_ikan`),
  CONSTRAINT `bongkaran_id_ikan_foreign` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`),
  CONSTRAINT `bongkaran_id_pelayaran_foreign` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.ikan_hasil_pelayaran definition

CREATE TABLE `ikan_hasil_pelayaran` (
  `id_hasil` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pelayaran` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `kategori_tangkapan` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pancingan_pribadi',
  `nama_penangkap` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `berat_hasil` decimal(15,2) NOT NULL,
  `harga_per_kg` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_hasil`),
  UNIQUE KEY `ikan_hasil_pelayaran_trip_ikan_kategori_penangkap_unique` (`id_pelayaran`,`id_ikan`,`kategori_tangkapan`,`nama_penangkap`),
  KEY `ikan_hasil_pelayaran_id_ikan_foreign` (`id_ikan`),
  KEY `ikan_hasil_pelayaran_id_pelayaran_idx` (`id_pelayaran`),
  CONSTRAINT `ikan_hasil_pelayaran_id_ikan_foreign` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`),
  CONSTRAINT `ikan_hasil_pelayaran_id_pelayaran_foreign` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=250 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.laporan_selisih_bongkaran definition

CREATE TABLE `laporan_selisih_bongkaran` (
  `id_laporan` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pelayaran` int unsigned NOT NULL,
  `total_berat_timbangan` decimal(15,2) NOT NULL,
  `total_berat_catatan` decimal(15,2) NOT NULL,
  `total_selisih` decimal(15,2) NOT NULL,
  `tanggal_laporan` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_laporan`),
  KEY `laporan_selisih_bongkaran_id_pelayaran_foreign` (`id_pelayaran`),
  CONSTRAINT `laporan_selisih_bongkaran_id_pelayaran_foreign` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.operasional definition

CREATE TABLE `operasional` (
  `id_operasional` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pelayaran` int unsigned NOT NULL,
  `id_master_operasional` int unsigned DEFAULT NULL,
  `jenis_biaya` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_operasional`),
  KEY `operasional_id_master_operasional_foreign` (`id_master_operasional`),
  KEY `idx_operasional_pelayaran_master` (`id_pelayaran`,`id_master_operasional`),
  CONSTRAINT `operasional_id_master_operasional_foreign` FOREIGN KEY (`id_master_operasional`) REFERENCES `master_operasional` (`id_master_operasional`),
  CONSTRAINT `operasional_id_pelayaran_foreign` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penjualan_item_storage_allocations definition

CREATE TABLE `penjualan_item_storage_allocations` (
  `id_alokasi_penjualan_item_storage` int unsigned NOT NULL AUTO_INCREMENT,
  `id_item` int unsigned NOT NULL,
  `id_storage` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `berat_alokasi` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_alokasi_penjualan_item_storage`),
  KEY `fk_penjualan_alloc_item` (`id_item`),
  KEY `fk_penjualan_alloc_ikan` (`id_ikan`),
  KEY `idx_penjualan_alloc_storage_ikan` (`id_storage`,`id_ikan`),
  CONSTRAINT `fk_penjualan_alloc_ikan` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`) ON DELETE CASCADE,
  CONSTRAINT `fk_penjualan_alloc_item` FOREIGN KEY (`id_item`) REFERENCES `penjualan_items` (`id_item`) ON DELETE CASCADE,
  CONSTRAINT `fk_penjualan_alloc_storage` FOREIGN KEY (`id_storage`) REFERENCES `storage_ikan` (`id_storage`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penyesuaian_stok_ikan_items definition

CREATE TABLE `penyesuaian_stok_ikan_items` (
  `id_penyesuaian_stok_item` int unsigned NOT NULL AUTO_INCREMENT,
  `id_penyesuaian_stok` int unsigned NOT NULL,
  `id_storage` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `delta_berat` decimal(15,2) NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_penyesuaian_stok_item`),
  KEY `fk_penyesuaian_stok_items_header` (`id_penyesuaian_stok`),
  KEY `penyesuaian_stok_ikan_items_id_storage_foreign` (`id_storage`),
  KEY `penyesuaian_stok_ikan_items_id_ikan_foreign` (`id_ikan`),
  CONSTRAINT `fk_penyesuaian_stok_items_header` FOREIGN KEY (`id_penyesuaian_stok`) REFERENCES `penyesuaian_stok_ikan` (`id_penyesuaian_stok`) ON DELETE CASCADE,
  CONSTRAINT `penyesuaian_stok_ikan_items_id_ikan_foreign` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`) ON DELETE CASCADE,
  CONSTRAINT `penyesuaian_stok_ikan_items_id_storage_foreign` FOREIGN KEY (`id_storage`) REFERENCES `storage_ikan` (`id_storage`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.stok_ikan_lots definition

CREATE TABLE `stok_ikan_lots` (
  `id_stok_ikan_lot` int unsigned NOT NULL AUTO_INCREMENT,
  `id_storage` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `id_pelayaran` int unsigned DEFAULT NULL,
  `source_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trip',
  `tanggal_lot` date NOT NULL,
  `berat_awal` decimal(15,2) NOT NULL,
  `berat_sisa` decimal(15,2) NOT NULL,
  `harga_per_kg` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_stok_ikan_lot`),
  KEY `fk_stok_ikan_lot_ikan` (`id_ikan`),
  KEY `fk_stok_ikan_lot_pelayaran` (`id_pelayaran`),
  KEY `idx_stok_ikan_lot_fifo` (`id_storage`,`id_ikan`,`tanggal_lot`),
  CONSTRAINT `fk_stok_ikan_lot_ikan` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`) ON DELETE CASCADE,
  CONSTRAINT `fk_stok_ikan_lot_pelayaran` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`) ON DELETE SET NULL,
  CONSTRAINT `fk_stok_ikan_lot_storage` FOREIGN KEY (`id_storage`) REFERENCES `storage_ikan` (`id_storage`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.stok_ikan_storage definition

CREATE TABLE `stok_ikan_storage` (
  `id_stok_storage` int unsigned NOT NULL AUTO_INCREMENT,
  `id_storage` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `stok_aktual` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_stok_storage`),
  UNIQUE KEY `stok_ikan_storage_storage_ikan_unique` (`id_storage`,`id_ikan`),
  KEY `stok_ikan_storage_id_ikan_foreign` (`id_ikan`),
  CONSTRAINT `stok_ikan_storage_id_ikan_foreign` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`) ON DELETE CASCADE,
  CONSTRAINT `stok_ikan_storage_id_storage_foreign` FOREIGN KEY (`id_storage`) REFERENCES `storage_ikan` (`id_storage`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fishery.penjualan_item_lot_allocations definition

CREATE TABLE `penjualan_item_lot_allocations` (
  `id_penjualan_item_lot_allocation` int unsigned NOT NULL AUTO_INCREMENT,
  `id_item` int unsigned NOT NULL,
  `id_stok_ikan_lot` int unsigned NOT NULL,
  `id_storage` int unsigned NOT NULL,
  `id_ikan` int unsigned NOT NULL,
  `id_pelayaran` int unsigned DEFAULT NULL,
  `berat_alokasi` decimal(15,2) NOT NULL,
  `harga_per_kg_lot` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_penjualan_item_lot_allocation`),
  KEY `fk_penjualan_lot_alloc_item` (`id_item`),
  KEY `fk_penjualan_lot_alloc_lot` (`id_stok_ikan_lot`),
  KEY `fk_penjualan_lot_alloc_storage` (`id_storage`),
  KEY `fk_penjualan_lot_alloc_ikan` (`id_ikan`),
  KEY `idx_penjualan_lot_alloc_pelayaran_ikan` (`id_pelayaran`,`id_ikan`),
  CONSTRAINT `fk_penjualan_lot_alloc_ikan` FOREIGN KEY (`id_ikan`) REFERENCES `master_ikan` (`id_ikan`) ON DELETE CASCADE,
  CONSTRAINT `fk_penjualan_lot_alloc_item` FOREIGN KEY (`id_item`) REFERENCES `penjualan_items` (`id_item`) ON DELETE CASCADE,
  CONSTRAINT `fk_penjualan_lot_alloc_lot` FOREIGN KEY (`id_stok_ikan_lot`) REFERENCES `stok_ikan_lots` (`id_stok_ikan_lot`) ON DELETE CASCADE,
  CONSTRAINT `fk_penjualan_lot_alloc_pelayaran` FOREIGN KEY (`id_pelayaran`) REFERENCES `pelayaran` (`id_pelayaran`) ON DELETE SET NULL,
  CONSTRAINT `fk_penjualan_lot_alloc_storage` FOREIGN KEY (`id_storage`) REFERENCES `storage_ikan` (`id_storage`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;