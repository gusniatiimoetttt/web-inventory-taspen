TYPE=VIEW
query=select `pd`.`id` AS `id`,`pd`.`no_dosir` AS `no_dosir`,`pd`.`nama_peserta` AS `nama_peserta`,`pd`.`nama_peminjam` AS `nama_peminjam`,`pd`.`jenis_berkas` AS `jenis_berkas`,`pd`.`tanggal_peminjaman` AS `tanggal_peminjaman`,`pd`.`tanggal_pengembalian` AS `tanggal_pengembalian`,`pd`.`status` AS `status`,`u`.`nama` AS `created_by_name`,`bi`.`no_rak` AS `no_rak`,`bi`.`no_box` AS `no_box`,`pd`.`created_at` AS `created_at` from ((`sistem_dosir`.`pinjaman_dosir` `pd` left join `sistem_dosir`.`users` `u` on(`pd`.`created_by` = `u`.`id`)) left join `sistem_dosir`.`berkas_inventory` `bi` on(`pd`.`berkas_id` = `bi`.`id`)) where `pd`.`status` <> \'dihapus_permanen\' order by `pd`.`created_at` desc
md5=62a804cd77a4cd23ece703862bc41274
updatable=0
algorithm=0
definer_user=root
definer_host=localhost
suid=2
with_check_option=0
timestamp=0001758545219001224
create-version=2
source=SELECT \n    pd.id,\n    pd.no_dosir,\n    pd.nama_peserta,\n    pd.nama_peminjam,\n    pd.jenis_berkas,\n    pd.tanggal_peminjaman,\n    pd.tanggal_pengembalian,\n    pd.status,\n    u.nama as created_by_name,\n    bi.no_rak,\n    bi.no_box,\n    pd.created_at\nFROM pinjaman_dosir pd\nLEFT JOIN users u ON pd.created_by = u.id\nLEFT JOIN berkas_inventory bi ON pd.berkas_id = bi.id\nWHERE pd.status != \'dihapus_permanen\'\nORDER BY pd.created_at DESC
client_cs_name=utf8mb4
connection_cl_name=utf8mb4_unicode_ci
view_body_utf8=select `pd`.`id` AS `id`,`pd`.`no_dosir` AS `no_dosir`,`pd`.`nama_peserta` AS `nama_peserta`,`pd`.`nama_peminjam` AS `nama_peminjam`,`pd`.`jenis_berkas` AS `jenis_berkas`,`pd`.`tanggal_peminjaman` AS `tanggal_peminjaman`,`pd`.`tanggal_pengembalian` AS `tanggal_pengembalian`,`pd`.`status` AS `status`,`u`.`nama` AS `created_by_name`,`bi`.`no_rak` AS `no_rak`,`bi`.`no_box` AS `no_box`,`pd`.`created_at` AS `created_at` from ((`sistem_dosir`.`pinjaman_dosir` `pd` left join `sistem_dosir`.`users` `u` on(`pd`.`created_by` = `u`.`id`)) left join `sistem_dosir`.`berkas_inventory` `bi` on(`pd`.`berkas_id` = `bi`.`id`)) where `pd`.`status` <> \'dihapus_permanen\' order by `pd`.`created_at` desc
mariadb-version=100432
